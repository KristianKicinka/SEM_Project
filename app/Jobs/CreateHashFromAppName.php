<?php
/**
 * @file CreateHashFromAppName.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Jobs;

use App\Exceptions\ApkDownloadException;
use App\Exceptions\AppUninstallationFailException;
use App\Exceptions\HashGenerationProcessFailed;
use App\Models\Emulator;
use App\Objects\CreateHash;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

    const PACKAGE_NAME_INPUT_TYPE = 'APP_NAME';
    const APK_DOWNLOADED_DIR = '/mnt/storage/app/public/uploads/apk_downloaded/';

class CreateHashFromAppName extends CreateHash implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $package_name;
    private array $pre_installed_apps;
    private bool $apk_clean_needed = false;
    private bool $apk_uninstall_needed = false;
    private string $apk_path;
    private ?Emulator $emulator = null;

    /**
     * @brief Create a new job instance
     * @return void
     */
    public function __construct($package_name, $hash_types, $ip_address, $channel_id, $process_id, $api_id){
        parent::__construct($hash_types, PACKAGE_NAME_INPUT_TYPE, $process_id, $ip_address, $channel_id, $package_name, $api_id);
        $this->package_name = $package_name;
    }

    /**
     * @brief Execute the job
     * @throws HashGenerationProcessFailed Hash process failed exception
     * @throws AppUninstallationFailException App uninstallation failed exception
     */
    public function handle(): void {

        try {
            Log::channel('devlog')->info('Hash creation process for package_name: {name} started!',
                ['name' => $this->package_name]);

            // Start processing job
            $this->hash_process_data->setProcessing();

            Log::channel('devlog')->info('After processing');

            // Download APK file
            $this->hash_process_data->nextProcessPart();

            $apk_file_name = trim($this->downloadApkFile($this->package_name));

            Log::channel('devlog')->info('After download file : {file}', ['file' => $apk_file_name]);

            $pcap_file_name = str_replace('.apk', '.pcap', $apk_file_name);

            $this->apk_path = APK_DOWNLOADED_DIR.$apk_file_name;

            Log::channel('devlog')->info('APK path: {path} ', ['path' => $this->apk_path]);

            $this->addFileToFiles($apk_file_name, 'APK', $this->apk_path);
            $this->apk_clean_needed = true;

            $this->emulator = $this->get_free_emulator();
            $this->set_emulator_working_state($this->emulator, true);

            // Get information's about APK file
            $this->hash_process_data->nextProcessPart();
            $this->package_name = trim($this->getAppPackageName($this->emulator, $this->apk_path));
            $version_name = trim($this->getAppVersionName($this->emulator, $this->apk_path));
            $application_name = trim($this->getAppName($this->emulator, $this->apk_path));

            $this->pre_installed_apps = $this->getPreInstalledApps();

            // App installation
            $this->hash_process_data->nextProcessPart();

            if (!in_array($this->package_name, $this->pre_installed_apps)) {
                $this->installAppOnEmulator($this->emulator, $this->apk_path, "APK");
                $this->apk_uninstall_needed = true;
            }

            // Network analysis
            $this->hash_process_data->nextProcessPart();
            $pcap_file_path = trim($this->createPcapFile($this->emulator, $pcap_file_name, $this->package_name));

            // Clear android emulator
            if ($this->apk_uninstall_needed && !in_array($this->package_name, $this->pre_installed_apps)){
                $this->uninstallAppOnEmulator($this->emulator, $this->package_name);
                $this->apk_uninstall_needed = false;
            }

            $this->set_emulator_working_state($this->emulator, false);

            // Create hashes
            $this->hash_process_data->nextProcessPart();
            $hashes = $this->createHashes($pcap_file_name, $pcap_file_path);

            $results = [
                'app_name' => $application_name,
                'package_name' => $this->package_name,
                'version' => $version_name,
                'hashes' => $hashes,
            ];

            // Save hashes to database
            $this->hash_process_data->nextProcessPart();
            $this->saveHashes($results);

            // Clear APK files for save storage space
            if($this->apk_clean_needed){
                $this->delete_apk_file($this->apk_path);
                $this->apk_clean_needed = false;
            }

            // Finish processing job
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

    /**
     * @brief The function ensures downloading file from Apkpure
     * @param string $package_name Application package name
     * @return string Downloaded file name
     * @throws ApkDownloadException APK download exception
     */
    private function downloadApkFile(string $package_name): string {

        // old "https://d.apkpure.com/b/APK/".$package_name."?version=latest";
        $url = "https://d.cdnpure.com/b/APK/".$package_name."?version=latest";

        $file_name = date('his')."_".$package_name.".apk";
        $download_dir = storage_path("app/public/uploads/apk_downloaded");

        $command = "aria2c -x 2 -s 2 -d ".$download_dir." -o ".$file_name." ".$url;

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful())
            throw new ApkDownloadException($process->getErrorOutput());

        return $file_name;
    }
}
