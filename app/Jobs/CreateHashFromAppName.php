<?php

namespace App\Jobs;

use App\Exceptions\ApkDownloadException;
use App\Objects\CreateHash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;



const APP_NAME_INPUT_TYPE = "APP_NAME";
const APK_DOWNLOADED_DIR = 'app/public/uploads/apk_downloaded/';

class CreateHashFromAppName extends CreateHash implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $package_name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($package_name, $hash_types, $frontend_id, $ip_address){
        parent::__construct($hash_types);
        parent::setFrontendID($frontend_id);
        parent::setIPaddress($ip_address);
        parent::setHashProcessData($frontend_id, APP_NAME_INPUT_TYPE, $ip_address);
        $this->package_name = $package_name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void {

        $hashes = [];

        Log::channel('devlog')
            ->info('Hash creation process for package_name: {name} started!', ['name' => $this->package_name]);

        $this->hash_process_data->setProcessing();

        // Download APK file
        $this->hash_process_data->nextProcessPart();

        $apk_file_name = trim($this->downloadApkFile($this->package_name));

        Log::channel('devlog')
            ->info('After downloading APK file | file name: {name}', ['name' => $apk_file_name]);

        $pcap_file_name = str_replace('.apk', '.pcap', $apk_file_name);
        Log::channel('devlog')
            ->info('After creating PCAP file | file name: {name}', ['name' => $pcap_file_name]);

        $apk_path = trim(storage_path(APK_DOWNLOADED_DIR).$apk_file_name);
        Log::channel('devlog')
            ->info('After creating APK path | APK path: {path}', ['path' => $apk_path]);


        $this->addFileToFiles($apk_file_name, 'APK', $apk_path);

        // Get information's about APK file
        $this->hash_process_data->nextProcessPart();
        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));
        $application_name = trim($this->getAppName($apk_path));

        Log::channel('devlog')
            ->info('After get app info | App package name: {package_name}', ['package_name' => $package_name]);
        Log::channel('devlog')
            ->info('After get app info | App version name: {version_name}', ['version_name' => $version_name]);
        Log::channel('devlog')
            ->info('After get app info | App name: {app_name}', ['app_name' => $application_name]);


        // App installation
        $this->hash_process_data->nextProcessPart();
        $this->installAppOnEmulator($apk_path);
        Log::channel('devlog')->info('App was installed on emulator');

        // Network analysis
        $this->hash_process_data->nextProcessPart();
        $pcap_file_path = trim($this->createPcapFile($pcap_file_name, $apk_path));

        // Clear android emulator
        $this->uninstallAppOnEmulator($package_name);
        Log::channel('devlog')->info('App was un-installed on emulator');

        // Create hashes
        $this->hash_process_data->nextProcessPart();
        $hashes = $this->createHashes($this->hash_types, $pcap_file_name, $pcap_file_path);

        Log::channel('devlog')
            ->info('After create hashes | hashes: {hashes}', ['hashes' => $hashes]);

        $results = [
            'app_name' => $application_name,
            'package_name' => $package_name,
            'app_version' => $version_name,
            'hashes' => $hashes,
        ];

        // Save hashes to database
        $this->hash_process_data->nextProcessPart();
        $this->saveHashes($results);

        Log::channel('devlog')
            ->info('After save hashes to database');

        $this->hash_process_data->setFinished();

    }

    /**
     * @param string $package_name
     * @return string
     */
    private function downloadApkFile(string $package_name): string {

        $url = "https://d.apkpure.com/b/APK/".$package_name."?version=latest";

        $file_prefix = date('his');

        $process = new Process(['python3', './scripts/download_apk_file.py', $url, $file_prefix]);
        $process->run();

        if (!$process->isSuccessful())
            throw new ApkDownloadException($process->getErrorOutput());

        return $process->getOutput();
    }
}
