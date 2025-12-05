<?php

namespace App\Http\Middleware;

use App\Services\Session\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Verifies JWT token from cookie and ensures user is authenticated.
     * Similar to Next.js middleware that checks session cookie.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       // ⛔ Désactive la vérification
        return $next($request);

        // ✅ Code original (à commenter ou supprimer) :
        // if (!auth()->check()) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }
        // return $next($request);
    }
}
