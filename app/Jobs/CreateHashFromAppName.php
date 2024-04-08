<?php

namespace App\Jobs;

use App\Exceptions\ApkDownloadException;
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
    private ?Emulator $emulator = null;

    /**
     * @brief Create a new job instance
     * @return void
     */
    public function __construct($package_name, $hash_types, $ip_address, $channel_id, $process_id){
        parent::__construct($hash_types, PACKAGE_NAME_INPUT_TYPE, $process_id, $ip_address, $channel_id, $package_name);
        $this->package_name = $package_name;
    }

    /**
     * @brief Execute the job
     * @throws HashGenerationProcessFailed Hash process failed exception
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

            $apk_path = APK_DOWNLOADED_DIR.$apk_file_name;

            Log::channel('devlog')->info('APK path: {path} ', ['path' => $apk_path]);

            $this->addFileToFiles($apk_file_name, 'APK', $apk_path);

            $emulator = $this->get_free_emulator();
            $this->emulator = $emulator;
            $this->set_emulator_working_state($emulator, true);

            // Get information's about APK file
            $this->hash_process_data->nextProcessPart();
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
            $pcap_file_path = trim($this->createPcapFile($emulator, $pcap_file_name, $apk_path));

            // Clear android emulator
            if (!in_array($package_name, $pre_installed_apps))
                $this->uninstallAppOnEmulator($emulator, $package_name);

            $this->set_emulator_working_state($emulator, false);

            // Create hashes
            $this->hash_process_data->nextProcessPart();
            $hashes = $this->createHashes($pcap_file_name, $pcap_file_path);

            $results = [
                'app_name' => $application_name,
                'package_name' => $package_name,
                'version' => $version_name,
                'hashes' => $hashes,
            ];

            // Save hashes to database
            $this->hash_process_data->nextProcessPart();
            $this->saveHashes($results);

            // Clear APK files for save storage space
            $this->delete_apk_file($apk_path);

            // Finish processing job
            $this->hash_process_data->setFinished();

        } catch(Exception $e){
            $this->hash_process_data->setFailed();
            if ($this->emulator)
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
