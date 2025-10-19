<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetPhpSettings
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
        // Set PHP settings for large file uploads
        ini_set('upload_max_filesize', '950M');
        ini_set('post_max_size', '950M');
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');
        
        return $next($request);
    }
}




