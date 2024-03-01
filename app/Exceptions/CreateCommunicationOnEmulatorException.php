<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class CreateCommunicationOnEmulatorException extends Exception
{
    public function render() {
        Log::channel('devlog')
            ->info('Create communication Failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
