<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class HashGenerationProcessFailed extends Exception
{
    public function render() {
        Log::channel('devlog')
            ->info('Hash generation process Failed! : {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
