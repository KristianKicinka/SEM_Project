<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class PcapFileNotFoundException extends Exception {
    
    public function render() {
        Log::channel('devlog')
            ->info('Pcap file not found! message: {message}', ['message' => $this->getMessage()]);
        return response()->json(['error' => $this->getMessage()], 400);
    }
}
