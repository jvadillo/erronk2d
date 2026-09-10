<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SetupController;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        $setupNavigation = [];
        if ($user && $user->role !== 'student') {
            foreach (SetupController::SECTIONS as $section => $label) {
                if ($section === 'registrations' && $user->role !== 'admin') {
                    continue;
                }
                $setupNavigation[] = ['section' => $section, 'label' => $label, 'url' => route('setup.section', ['section' => $section])];
            }
        }

        return [...parent::share($request), ...(app()->environment('testing') ? ['test_environment' => true] : []), 'auth' => $user ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'permissions' => $user->role === 'admin' ? User::PERMISSIONS : ($user->permissions ?? [])] : null,
            'setupNavigation' => $setupNavigation,
            'flash' => ['success' => fn () => $request->session()->get('success')]];
    }
}
