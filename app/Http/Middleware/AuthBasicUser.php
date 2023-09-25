<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthBasicUser {
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {

        try {
            // Verify the JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Check if the user has the "admin" role
            if ($user->hasRole('basic_user')) {
                return $next($request);
            }

            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (Exception $e) {
            // Token is invalid or user is not authenticated
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
