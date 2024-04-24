<?php
/**
 * @file AuthExternalAPI.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Http\Response;
use \Illuminate\Http\JsonResponse;

class AuthExternalAPI {

    /**
     * @brief The function ensures the authentication of external api route users
     * @param Request  $request HTTP request
     * @param Closure(Request): (Response|RedirectResponse) $next Next route to redirect
     * @return JsonResponse Error response
     */
    public function handle(Request $request, Closure $next): JsonResponse {
        $auth_key = $request->input('auth_key');

        $user = User::where('api_auth_key', '=', $auth_key)->first();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
