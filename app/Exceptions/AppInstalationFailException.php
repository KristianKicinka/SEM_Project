<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class AppInstalationFailException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('App installation failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
