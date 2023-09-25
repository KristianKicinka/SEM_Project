<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class AppUninstalationFailException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('App uninstallation failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
