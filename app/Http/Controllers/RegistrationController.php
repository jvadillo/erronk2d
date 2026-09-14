<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Models\AuditEvent;
use App\Models\Enrollment;
use App\Models\GoogleRegistration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function update(Request $request, GoogleRegistration $registration): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'role' => 'required_if:decision,approve|in:student,teacher',
            'classroom_id' => 'exclude_unless:decision,approve|exclude_unless:role,student|required|integer|exists:classrooms,id',
        ], ['classroom_id.required' => 'Selecciona una clase para el estudiante.']);
        try {
            DB::transaction(function () use ($request, $registration, $data) {
                $registration = GoogleRegistration::lockForUpdate()->findOrFail($registration->id);
                if ($registration->status !== 'pending') {
                    throw ValidationException::withMessages(['decision' => 'Esta solicitud ya se ha revisado. Actualiza la página.']);
                }
                $user = null;
                $class = null;
                if ($data['decision'] === 'approve' && $data['role'] === 'student') {
                    app(AcademicContext::class)->requireWritable($request);
                    $class = app(AcademicContext::class)->classrooms($request->user())->findOrFail($data['classroom_id']);
                }
                if ($data['decision'] === 'approve') {
                    if (User::whereRaw('LOWER(email) = ?', [$registration->email])->orWhere('google_id', $registration->google_id)->exists()) {
                        throw ValidationException::withMessages(['decision' => 'Ya existe una cuenta con este correo o cuenta Google. Debe vincularse desde la pantalla de acceso.']);
                    }
                    $user = new User([
                        'name' => $registration->name,
                        'email' => $registration->email,
                        'password' => Str::random(64),
                        'role' => $data['role'],
                        'active' => true,
                        'permissions' => [],
                    ]);
                    $user->forceFill(['google_id' => $registration->google_id, 'email_verified_at' => now()])->save();
                    if ($class) {
                        Enrollment::create(['classroom_id' => $class->id, 'student_id' => $user->id]);
                    }
                }
                $registration->update(['status' => $user ? 'approved' : 'rejected', 'reviewed_by' => $request->user()->id, 'user_id' => $user?->id]);
                AuditEvent::create(['user_id' => $request->user()->id, 'action' => 'registration.'.$registration->status,
                    'after' => ['registration_id' => $registration->id, 'user_id' => $user?->id, 'role' => $user?->role, 'classroom_id' => $class?->id]]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['decision' => 'Ya existe una cuenta con este correo o cuenta Google. Actualiza la página.']);
        }

        return back()->with('success', $data['decision'] === 'approve' ? 'Cuenta aprobada. Ya puede acceder con Google.' : 'Solicitud rechazada.');
    }
}
