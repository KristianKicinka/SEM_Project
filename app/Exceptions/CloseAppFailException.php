<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class CloseAppFailException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('Close app failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
