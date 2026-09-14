<?php

namespace App\Http\Middleware;

use App\Domain\AcademicContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademicContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app(AcademicContext::class)->resolve($request);

        return $next($request);
    }
}
