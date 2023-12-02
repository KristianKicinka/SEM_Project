<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Objects\CreateHash;

const APK_INPUT_TYPE = "APK_FILE";
const APK_INSERTED_DIR = 'app/public/uploads/apk_inserted/';

class CreateHashFromAPK extends CreateHash implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $apk_file_name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($apk_file_name, $hash_types, $ip_address, $process_id){
        parent::__construct($hash_types, APK_INPUT_TYPE, $process_id, $ip_address);
        $this->apk_file_name = $apk_file_name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void {
        $hashes = [];

        $this->hash_process_data->setProcessing();

        // Get and save APK file
        $this->hash_process_data->nextProcessPart();

        $pcap_file_name = str_replace('.apk', '.pcap', $this->apk_file_name);
        $apk_path = storage_path(APK_INSERTED_DIR).$this->apk_file_name;

        $this->addFileToFiles($this->apk_file_name, 'APK', $apk_path);

        // Get information's about APK file
        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));
        $application_name = trim($this->getAppName($apk_path));

        // App installation
        $this->hash_process_data->nextProcessPart();
        $this->installAppOnEmulator($apk_path);

        // Network analysis
        $this->hash_process_data->nextProcessPart();
        $pcap_file_path = $this->createPcapFile($pcap_file_name, $apk_path);

        // Clear android emulator
        $this->uninstallAppOnEmulator($package_name);

        // Create hashes
        $this->hash_process_data->nextProcessPart();
        $hashes = $this->createHashes($this->hash_types, $pcap_file_name, $pcap_file_path);

        $db_data = [
            'app_name' => $application_name,
            'package_name' => $package_name,
            'version' => $version_name,
            'hashes' => $hashes,
        ];

        // Save hashes to database
        $this->hash_process_data->nextProcessPart();
        //dd($db_data);
        $this->saveHashes($db_data);

        $this->hash_process_data->setFinished();
    }
}
