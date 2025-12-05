<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!$this->sessionService->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $companyId = $this->sessionService->getCurrentCompanyId();

        if (!in_array(Auth::user()->role, $roles)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return $next($request);
    }
}
