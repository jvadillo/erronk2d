<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Classroom;
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
        if ($r->kind === 'module') {
            $r->validate(['classroom_id' => 'required|exists:classrooms,id']);
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
        $required = $r->kind === 'module' ? ['name', 'code', 'teacher_email'] : ['name', 'email'];
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
            $rules = $r->kind === 'module' ? ['name' => 'required|string|max:150', 'code' => 'required|string|max:30', 'teacher_email' => 'required|email'] : ['name' => 'required|string|max:150', 'email' => 'required|email|max:255|unique:users,email'];
            $validator = Validator::make($data, $rules);
            $key = strtolower($data[$r->kind === 'module' ? 'code' : 'email'] ?? '');
            $teacher = $r->kind === 'module' ? User::where('email', $data['teacher_email'] ?? '')->whereIn('role', ['teacher', 'admin'])->where('active', true)->first() : null;
            if ($validator->fails() || isset($seen[$key]) || ($r->kind !== 'module' && User::whereRaw('lower(email) = ?', [$key])->exists()) || ($r->kind === 'module' && (! $teacher || Module::where('classroom_id', $r->classroom_id)->whereRaw('lower(code) = ?', [$key])->exists()))) {
                $errors[] = ['row' => $i + 2, 'message' => $validator->fails() ? implode(' ', $validator->errors()->all()) : 'Duplicado o profesor responsable inexistente.'];
            } else {
                $valid[] = [...$data, 'teacher_id' => $teacher?->id];
            }
            $seen[$key] = true;
        }
        abort_if(count($valid) === 0 && count($errors) === 0, 422, 'El archivo está vacío.');
        if ($r->boolean('commit') && count($errors) === 0) {
            DB::transaction(function () use ($valid, $r) {
                foreach ($valid as $row) {
                    if ($r->kind === 'module') {
                        $module = Module::create(['name' => $row['name'], 'code' => $row['code'], 'classroom_id' => $r->classroom_id]);
                        $module->teachers()->attach($row['teacher_id']);
                        $module->classroom->users()->syncWithoutDetaching([$row['teacher_id']]);
                    } else {
                        $user = User::create(['name' => $row['name'], 'email' => strtolower($row['email']), 'role' => $r->kind, 'password' => Str::random(64), 'permissions' => []]);
                        if ($r->classroom_id) {
                            $class = Classroom::findOrFail($r->classroom_id);
                            abort_if($class->challenges()->exists(), 422, 'Importa en una clase sin retos para conservar las matrículas históricas.');
                            $class->users()->attach($user->id);
                        }
                    }
                }
                AuditEvent::create(['user_id' => $r->user()->id, 'action' => 'import.'.$r->kind, 'after' => ['count' => count($valid)]]);
            });
        }

        return response()->json(['count' => count($valid), 'errors' => $errors, 'preview' => array_slice($valid, 0, 10), 'committed' => $r->boolean('commit') && count($errors) === 0]);
    }
}
