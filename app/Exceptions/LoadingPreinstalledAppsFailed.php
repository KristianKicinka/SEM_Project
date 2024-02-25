<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class LoadingPreinstalledAppsFailed extends Exception
{
    public function render() {
        Log::channel('devlog')
            ->info('Loading preinstalled apps failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
