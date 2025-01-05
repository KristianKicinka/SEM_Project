<?php

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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use App\Objects\CreateHash;
use Exception;

const XAPK_INPUT_TYPE = "XAPK_FILE";
const XAPK_INSERTED_DIR = 'public/uploads/xapk_inserted/';
const XAPK_DOCKER_DIR = '/storage/app/public/uploads/xapk_inserted/';

class CreateHashFromXAPK extends CreateHash implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $xapk_file_name;
    private string $xapk_folder_path;
    private string $package_name;
    private array $pre_installed_apps;
    private bool $xapk_clean_needed = false;
    private bool $xapk_uninstall_needed = false;
    private string $xapk_path;
    private ?Emulator $emulator = null;

    /**
     * Create a new job instance.
     */
    public function __construct($xapk_file_name, $hash_types, $ip_address, $channel_id, $process_id, $process_name, $api_id) {
        parent::__construct($hash_types, XAPK_INPUT_TYPE, $process_id, $ip_address, $channel_id, $process_name, $api_id);
        $this->xapk_file_name = $xapk_file_name;
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

            $pcap_file_name = str_replace('.xapk', '.pcap', $this->xapk_file_name);
            $this->xapk_path = XAPK_INSERTED_DIR.$this->xapk_file_name;

            // Save APK file to storage
            $this->addFileToFiles($this->xapk_file_name, 'XAPK', $this->xapk_path);
            $this->xapk_clean_needed = true;

            // Taking free emulator
            $this->emulator = $this->get_free_emulator();
            $this->set_emulator_working_state($this->emulator, true);

            // extract xapk file
            $this->hash_process_data->nextProcessPart();

            $this->xapk_folder_path = $this->extractXAPKfile(XAPK_INSERTED_DIR.$this->xapk_file_name);
            Log::channel('devlog')->info('Manifest file: {manifest_file}', ['manifest_file' => $this->xapk_folder_path."/manifest.json"]);

            $json_content = File::get($this->xapk_folder_path."/manifest.json");
            $manifest_data = json_decode($json_content, true);

            // Get information's about APK file
            $this->package_name = trim($manifest_data["package_name"]);
            Log::channel('devlog')->info('Package name: {package_name}', ['package_name' => $this->package_name]);

            $version_name = trim($manifest_data["version_name"]);
            Log::channel('devlog')->info('Version name: {version_name}', ['version_name' => $version_name]);

            $application_name = trim($manifest_data["name"]);
            Log::channel('devlog')->info('Application name: {app_name}', ['app_name' => $application_name]);


            $this->pre_installed_apps = $this->getPreInstalledApps();

            // App installation
            $this->hash_process_data->nextProcessPart();

            Log::channel('devlog')->info('XAPK folder path: {folder_path}', ['folder_path' => $this->xapk_folder_path]);

            if (!in_array($this->package_name, $this->pre_installed_apps)) {
                $this->installAppOnEmulator($this->emulator, $this->xapk_folder_path, "XAPK");
                $this->xapk_uninstall_needed = true;
            }

            // Network analysis
            $this->hash_process_data->nextProcessPart();
            $pcap_file_path = $this->createPcapFile($this->emulator, $pcap_file_name, $this->package_name);

            // Clear android emulator
            if ($this->xapk_uninstall_needed && !in_array($this->package_name, $this->pre_installed_apps)) {
                $this->uninstallAppOnEmulator($this->emulator, $this->package_name);
                $this->xapk_uninstall_needed = false;
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
            if($this->xapk_clean_needed){
                $this->delete_apk_file($this->xapk_path);
                $this->xapk_clean_needed = false;
            }

            // Set finished state
            $this->hash_process_data->setFinished();

        } catch(Exception $e){
            // Process job exception
            $this->hash_process_data->setFailed();

            // Clear APK files if needed
            if($this->xapk_clean_needed)
                $this->delete_apk_file($this->xapk_path);

            // Uninstall app from emulator if process before uninstallation
            if($this->xapk_uninstall_needed && !in_array($this->package_name, $this->pre_installed_apps))
                $this->uninstallAppOnEmulator($this->emulator, $this->package_name);

            // Free emulator
            if($this->emulator)
                $this->set_emulator_working_state($this->emulator, false);

            throw new HashGenerationProcessFailed($e);
        }
    }
}
