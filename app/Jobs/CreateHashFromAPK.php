<?php

namespace App\Jobs;

use App\Exceptions\HashGenerationProcessFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Objects\CreateHash;
use Exception;

const APK_INPUT_TYPE = "APK_FILE";
const APK_INSERTED_DIR = '/mnt/storage/app/public/uploads/apk_inserted/';

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

        try {
            $this->hash_process_data->setProcessing();

            // Get and save APK file
            $this->hash_process_data->nextProcessPart();

            $pcap_file_name = str_replace('.apk', '.pcap', $this->apk_file_name);
            $apk_path = APK_INSERTED_DIR.$this->apk_file_name;

            $this->addFileToFiles($this->apk_file_name, 'APK', $apk_path);

            // Get information's about APK file
            $package_name = trim($this->getAppPackageName($apk_path));
            $version_name = trim($this->getAppVersionName($apk_path));
            $application_name = trim($this->getAppName($apk_path));

            $pre_installed_apps = $this->getPreInstlledApps();

            // App installation
            $this->hash_process_data->nextProcessPart();

            if (!in_array($package_name, $pre_installed_apps))
                $this->installAppOnEmulator($apk_path);

            // Network analysis
            $this->hash_process_data->nextProcessPart();
            $pcap_file_path = $this->createPcapFile($pcap_file_name, $apk_path);

            // Clear android emulator
            if (!in_array($package_name, $pre_installed_apps))
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

            Log::channel('devlog')->info('DB_DATA : {name}', ['name' => $db_data]);

            // Save hashes to database
            $this->hash_process_data->nextProcessPart();
            $this->saveHashes($db_data);

            $this->hash_process_data->setFinished();

        } catch(Exception $e){
            $this->hash_process_data->setFailed();
            throw new HashGenerationProcessFailed($e);
        }
    }
}
