<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class HashGeneratorFailException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('Hash generation failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
