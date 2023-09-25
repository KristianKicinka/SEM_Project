<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class ApkDownloadException extends Exception {

    public function render() {
        Log::channel('devlog')
            ->info('APK download failed! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
