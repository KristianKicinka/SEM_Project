<?php
/**
 * @file Authenticate.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware {

    /**
     * @brief The function ensures the getting path to redirect route for non auth users
     * @param  Request  $request HTTP request data
     * @return string|null Route to redirect
     */
    protected function redirectTo(Request $request): ?string {
        if (! $request->expectsJson()) {
            return route('login');
        }
        return null;
    }
}
