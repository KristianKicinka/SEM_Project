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
     * @brief The function ensures the basic user authentication
     * @param Request $request HTTP request data
     * @param Closure(Request): (Response|RedirectResponse) $next Next route to redirect
     * @return JsonResponse Error response
     */
    public function handle(Request $request, Closure $next): JsonResponse {

        try {
            // Verify the JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Check if the user has the "admin" role
            if ($user->role === 'basic_user') {
                return $next($request);
            }

            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (Exception $e) {
            // Token is invalid or user is not authenticated
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
