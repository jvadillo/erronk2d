<?php

namespace App\Http\Middleware;

use App\Domain\AcademicContext;
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
            foreach (array_keys(SetupController::SECTIONS) as $section) {
                if (in_array($section, SetupController::ADMIN_SECTIONS, true) && $user->role !== 'admin') {
                    continue;
                }
                $setupNavigation[] = ['section' => $section, 'label' => SetupController::NAVIGATION_LABELS[$section], 'url' => route('setup.section', ['section' => $section])];
            }
        }

        return [...parent::share($request), ...(app()->environment('testing') ? ['test_environment' => true] : []), 'auth' => $user ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'permissions' => array_values(array_filter(User::PERMISSIONS, fn ($permission) => $user->allows($permission)))] : null,
            'academic' => fn () => $user ? ['year' => app(AcademicContext::class)->year()?->only(['id', 'name', 'is_open']), 'years' => app(AcademicContext::class)->availableYears($user)->get(['id', 'name', 'is_open']), 'switch_url' => route('academic-context.update')] : null,
            'setupNavigation' => $setupNavigation,
            'flash' => ['success' => fn () => $request->session()->get('success')]];
    }
}
