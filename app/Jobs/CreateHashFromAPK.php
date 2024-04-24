<?php
/**
 * @file CreateHashFromAPK.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Jobs;

use App\Exceptions\AppUninstallationFailException;
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
    private string $package_name;
    private array $pre_installed_apps;
    private bool $apk_clean_needed = false;
    private bool $apk_uninstall_needed = false;
    private string $apk_path;
    private ?Emulator $emulator = null;

    /**
     * @brief Create a new job instance.
     * @return void
     */
    public function __construct($apk_file_name, $hash_types, $ip_address, $channel_id, $process_id, $process_name, $api_id){
        parent::__construct($hash_types, APK_INPUT_TYPE, $process_id, $ip_address, $channel_id, $process_name, $api_id);
        $this->apk_file_name = $apk_file_name;
    }

    /**
     * @brief Execute the job.
     * @throws HashGenerationProcessFailed
     * @throws AppUninstallationFailException
     */
    public function handle(): void {

        try {
            // Start processing job
            $this->hash_process_data->setProcessing();

            // Get and save APK file
            $this->hash_process_data->nextProcessPart();

            $pcap_file_name = str_replace('.apk', '.pcap', $this->apk_file_name);
            $this->apk_path = APK_INSERTED_DIR.$this->apk_file_name;

            // Save APK file to storage
            $this->addFileToFiles($this->apk_file_name, 'APK', $this->apk_path);
            $this->apk_clean_needed = true;

            // Taking free emulator
            $this->emulator = $this->get_free_emulator();
            $this->set_emulator_working_state($this->emulator, true);

            // Get information's about APK file
            $this->package_name = trim($this->getAppPackageName($this->emulator, $this->apk_path));
            $version_name = trim($this->getAppVersionName($this->emulator, $this->apk_path));
            $application_name = trim($this->getAppName($this->emulator, $this->apk_path));

            $this->pre_installed_apps = $this->getPreInstalledApps();

            // App installation
            $this->hash_process_data->nextProcessPart();

            if (!in_array($this->package_name, $this->pre_installed_apps)) {
                $this->installAppOnEmulator($this->emulator, $this->apk_path);
                $this->apk_uninstall_needed = true;
            }

            // Network analysis
            $this->hash_process_data->nextProcessPart();
            $pcap_file_path = $this->createPcapFile($this->emulator, $pcap_file_name, $this->apk_path);

            // Clear android emulator
            if ($this->apk_uninstall_needed && !in_array($this->package_name, $this->pre_installed_apps)) {
                $this->uninstallAppOnEmulator($this->emulator, $this->package_name);
                $this->apk_uninstall_needed = false;
            }

            // Free emulator
            $this->set_emulator_working_state($this->emulator, false);

            // Create hashes
            $this->hash_process_data->nextProcessPart();
            $hashes = $this->createHashes($pcap_file_name, $pcap_file_path);

            $db_data = [
                'app_name' => $application_name,
                'package_name' => $this->package_name,
                'version' => $version_name,
                'hashes' => $hashes,
            ];

            Log::channel('devlog')->info('DB_DATA : {name}', ['name' => $db_data]);

            // Save hashes to database
            $this->hash_process_data->nextProcessPart();
            $this->saveHashes($db_data);

            // Clear APK files for save storage space
            if($this->apk_clean_needed){
                $this->delete_apk_file($this->apk_path);
                $this->apk_clean_needed = false;
            }

            // Set finished state
            $this->hash_process_data->setFinished();

        } catch(Exception $e){
            // Process job exception
            $this->hash_process_data->setFailed();

            // Clear APK files if needed
            if($this->apk_clean_needed)
                $this->delete_apk_file($this->apk_path);

            // Uninstall app from emulator if process before uninstallation
            if($this->apk_uninstall_needed && !in_array($this->package_name, $this->pre_installed_apps))
                $this->uninstallAppOnEmulator($this->emulator, $this->package_name);

            // Free emulator
            if($this->emulator)
                $this->set_emulator_working_state($this->emulator, false);

            throw new HashGenerationProcessFailed($e);
        }
    }
}
