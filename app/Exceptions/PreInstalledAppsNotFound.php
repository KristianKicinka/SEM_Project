<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;


class PreInstalledAppsNotFound extends Exception
{
    public function render() {
        Log::channel('devlog')
            ->info('Pre installed apps not found! : {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
