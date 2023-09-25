<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class PackageNameNotFoundException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('Package name not found: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }

}
