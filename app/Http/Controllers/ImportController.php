<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Models\AuditEvent;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Reader;

class ImportController extends Controller
{
    public function import(Request $r): JsonResponse
    {
        $r->validate(['kind' => 'required|in:student,teacher,module', 'file' => 'required|file|max:2048|extensions:csv,xlsx', 'commit' => 'required|boolean', 'classroom_id' => 'nullable|exists:classrooms,id']);
        $permission = ['student' => 'manage_students', 'teacher' => 'manage_teachers', 'module' => 'manage_modules'][$r->kind];
        abort_unless($r->user()->allows($permission), 403);
        $class = null;
        if ($r->kind === 'student') {
            app(AcademicContext::class)->requireWritable($r);
            $r->validate(['classroom_id' => 'required|integer'], ['classroom_id.required' => 'Selecciona un grupo antes de importar.']);
            $class = app(AcademicContext::class)->classrooms($r->user())->findOrFail($r->integer('classroom_id'));
        } else {
            abort_unless($r->user()->role === 'admin', 403);
        }
        if ($r->kind === 'module') {
            $r->validate(['cycle_id' => 'required|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4']);
        }
        $path = $r->file('file')->getRealPath();
        $rows = [];
        if (strtolower($r->file('file')->getClientOriginalExtension()) === 'xlsx') {
            $zip = new \ZipArchive;
            abort_unless($zip->open($path) === true, 422, 'Archivo Excel no válido.');
            $expanded = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $expanded += $zip->statIndex($i)['size'];
            }
            $zip->close();
            abort_if($expanded > 20 * 1024 * 1024, 422, 'El Excel descomprimido supera el tamaño permitido.');
            $reader = new Reader;
            try {
                $reader->open($path);
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $rows[] = $row->toArray();
                        abort_if(count($rows) > 1001, 422, 'Máximo 1000 filas.');
                    } break;
                }
            } catch (OpenSpoutException $exception) {
                throw ValidationException::withMessages(['file' => 'El archivo no contiene un libro Excel válido.']);
            } finally {
                $reader->close();
            }
        } else {
            $handle = fopen($path, 'r');
            $line = fgets($handle) ?: '';
            rewind($handle);
            $delimiter = substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
            try {
                while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                    $rows[] = $row;
                    abort_if(count($rows) > 1001, 422, 'Máximo 1000 filas.');
                }
            } finally {
                fclose($handle);
            }
        }
        $headerRow = array_shift($rows) ?? [];
        abort_if(collect($headerRow)->contains(fn ($value) => ! is_scalar($value) && $value !== null), 422, 'Las cabeceras deben contener texto.');
        $header = array_map(fn ($v) => strtolower(trim((string) $v, "\xEF\xBB\xBF \t\n\r\0\x0B")), $headerRow);
        $required = $r->kind === 'module' ? ['name', 'code'] : ['name', 'email'];
        abort_if(array_diff($required, $header) || count($header) !== count(array_unique($header)), 422, 'Cabeceras requeridas: '.implode(', ', $required));
        $valid = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $i => $row) {
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            if (count($row) !== count($header)) {
                $errors[] = ['row' => $i + 2, 'message' => 'Número de columnas incorrecto.'];

                continue;
            }
            if (collect($row)->contains(fn ($value) => ! is_scalar($value) && $value !== null)) {
                $errors[] = ['row' => $i + 2, 'message' => 'Usa texto en los campos de nombre, correo y código.'];

                continue;
            }
            $data = array_combine($header, array_map(fn ($v) => trim((string) $v), $row));
            $rules = $r->kind === 'module' ? ['name' => 'required|string|max:150', 'code' => 'required|string|max:30'] : ['name' => 'required|string|max:150', 'email' => 'required|email|max:255'];
            $validator = Validator::make($data, $rules);
            $key = strtolower($data[$r->kind === 'module' ? 'code' : 'email'] ?? '');
            $existing = $r->kind !== 'module' ? User::whereRaw('LOWER(email) = ?', [$key])->first() : null;
            $duplicateModule = $r->kind === 'module' && Module::where('cycle_id', $r->integer('cycle_id'))->where('level', $r->integer('level'))->whereRaw('LOWER(code) = ?', [$key])->exists();
            $invalidAccount = $existing && ($r->kind !== 'student' || $existing->role !== 'student' || ! $existing->active);
            if ($validator->fails() || isset($seen[$key]) || $invalidAccount || $duplicateModule) {
                $errors[] = ['row' => $i + 2, 'message' => $validator->fails() ? implode(' ', $validator->errors()->all()) : 'Duplicado o cuenta no disponible para matricular.'];
            } else {
                $valid[] = ['name' => $data['name'], ...($r->kind === 'module' ? ['code' => $data['code']] : ['email' => $key])];
            }
            $seen[$key] = true;
        }
        abort_if(count($valid) === 0 && count($errors) === 0, 422, 'El archivo está vacío.');
        if ($r->boolean('commit') && count($errors) === 0) {
            DB::transaction(function () use ($valid, $r, $class) {
                foreach ($valid as $row) {
                    if ($r->kind === 'module') {
                        Module::create(['name' => $row['name'], 'code' => $row['code'], 'cycle_id' => $r->integer('cycle_id'), 'level' => $r->integer('level')]);
                    } else {
                        $user = User::whereRaw('LOWER(email) = ?', [$row['email']])->lockForUpdate()->first()
                            ?? User::create(['email' => $row['email'], 'name' => $row['name'], 'role' => $r->kind, 'password' => Str::random(64)]);
                        abort_unless($user->role === $r->kind && $user->active, 422, 'Cuenta no disponible.');
                        if ($class) {
                            Enrollment::updateOrCreate(['classroom_id' => $class->id, 'student_id' => $user->id], ['ended_at' => null]);
                        }
                    }
                }
                AuditEvent::create(['user_id' => $r->user()->id, 'action' => 'import.'.$r->kind, 'after' => ['count' => count($valid)]]);
            });
        }

        return response()->json(['count' => count($valid), 'errors' => $errors, 'classroom' => $class?->only(['id', 'name']), 'preview' => array_slice($valid, 0, 10), 'committed' => $r->boolean('commit') && count($errors) === 0]);
    }
}
