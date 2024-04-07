<?php

namespace App\Jobs;

use App\Exceptions\HashGenerationProcessFailed;
use App\Models\Emulator;
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
    private Emulator $emulator;

    /**
     * @brief Create a new job instance.
     * @return void
     */
    public function __construct($apk_file_name, $hash_types, $ip_address, $channel_id, $process_id, $process_name){
        parent::__construct($hash_types, APK_INPUT_TYPE, $process_id, $ip_address, $channel_id, $process_name);
        $this->apk_file_name = $apk_file_name;
    }

    /**
     * @brief Execute the job.
     * @throws HashGenerationProcessFailed
     */
    public function handle(): void {

        try {
            // Start processing job
            $this->hash_process_data->setProcessing();

            // Get and save APK file
            $this->hash_process_data->nextProcessPart();

            $pcap_file_name = str_replace('.apk', '.pcap', $this->apk_file_name);
            $apk_path = APK_INSERTED_DIR.$this->apk_file_name;
            $this->addFileToFiles($this->apk_file_name, 'APK', $apk_path);

            // Taking free emulator
            $emulator = $this->get_free_emulator();
            $this->emulator = $emulator;
            $this->set_emulator_working_state($emulator, true);

            // Get information's about APK file
            $package_name = trim($this->getAppPackageName($emulator, $apk_path));
            $version_name = trim($this->getAppVersionName($emulator, $apk_path));
            $application_name = trim($this->getAppName($emulator, $apk_path));

            $pre_installed_apps = $this->getPreInstalledApps();

            // App installation
            $this->hash_process_data->nextProcessPart();

            if (!in_array($package_name, $pre_installed_apps))
                $this->installAppOnEmulator($emulator, $apk_path);

            // Network analysis
            $this->hash_process_data->nextProcessPart();
            $pcap_file_path = $this->createPcapFile($emulator, $pcap_file_name, $apk_path);

            // Clear android emulator
            if (!in_array($package_name, $pre_installed_apps))
                $this->uninstallAppOnEmulator($emulator, $package_name);

            // Free emulator
            $this->set_emulator_working_state($emulator, false);

            // Create hashes
            $this->hash_process_data->nextProcessPart();
            $hashes = $this->createHashes($pcap_file_name, $pcap_file_path);

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

            // Clear APK files for save storage space
            $this->delete_apk_file($apk_path);

            // Set finished state
            $this->hash_process_data->setFinished();

        } catch(Exception $e){
            // Process job exception
            $this->hash_process_data->setFailed();
            $this->set_emulator_working_state($this->emulator, false);
            throw new HashGenerationProcessFailed($e);
        }
    }
}
