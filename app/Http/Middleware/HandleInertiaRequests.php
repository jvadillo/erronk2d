<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [...parent::share($request), 'auth' => $user ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'permissions' => $user->role === 'admin' ? User::PERMISSIONS : ($user->permissions ?? [])] : null,
            'flash' => ['success' => fn () => $request->session()->get('success')]];
    }
}
