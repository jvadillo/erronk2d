<?php

namespace App\Http\Middleware;

use App\Domain\AcademicContext;
use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademicContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app(AcademicContext::class)->resolve($request);

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $year = app(AcademicContext::class)->year();
            if ($year) {
                $request->attributes->set('academic_year', AcademicYear::whereKey($year->id)->lockForUpdate()->first());
            }

            return $next($request);
        });
    }
}
