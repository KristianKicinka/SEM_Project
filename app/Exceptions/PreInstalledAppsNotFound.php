<?php
/**
 * @file PreInstalledAppsNotFound.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PreInstalledAppsNotFound extends Exception {

    /**
     * @brief The function serves exception rendering
     * @return JsonResponse Error message
     */
    public function render(): JsonResponse {
        Log::channel('devlog')
            ->info('Pre installed apps not found! : {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
