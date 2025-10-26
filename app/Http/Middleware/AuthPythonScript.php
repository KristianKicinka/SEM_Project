<?php
/**
 * @file AuthPythonScript.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthPythonScript
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for API key in header
        $apiKey = $request->header('Authorization');
        
        if (!$apiKey) {
            return response()->json(['error' => 'Missing API key'], 401);
        }
        
        // Remove 'Bearer ' prefix if present
        if (str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }
        
        // Simple API key validation (in production, use proper API key management)
        if (!$this->validateApiKey($apiKey)) {
            Log::warning('Invalid API key used for Python script access', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'api_key' => substr($apiKey, 0, 10) . '...'
            ]);
            
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        
        return $next($request);
    }
    
    /**
     * Validate API key for Python scripts
     *
     * @param string $apiKey
     * @return bool
     */
    private function validateApiKey(string $apiKey): bool
    {
        // Simple validation - in production, use proper API key management
        // For now, accept keys that start with 'python_hash_generator_key_'
        return str_starts_with($apiKey, 'python_hash_generator_key_');
    }
}
