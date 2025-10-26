<?php
/**
 * @file CreateHash.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Objects;

use App\Exceptions\AppInstallationFailException;
use App\Exceptions\AppNameNotFoundException;
use App\Exceptions\AppUninstallationFailException;
use App\Exceptions\AppVersionNotFoundException;
use App\Exceptions\CloseAppFailException;
use App\Exceptions\CreateCommunicationOnEmulatorException;
use App\Exceptions\HashGeneratorFailException;
use App\Exceptions\LoadingPreinstalledAppsFailed;
use App\Exceptions\PackageNameNotFoundException;
use App\Exceptions\RunAppFailException;
use App\Exceptions\XapkFileExtractException;
use App\Models\Application;
use App\Models\Emulator;
use App\Models\File;
use App\Models\Hash;
use App\Models\Process as ProcessModel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

// Hash generator python script path
const HASH_SCRIPT_PATH = 'scripts/hash_generator.py';
// Path to pcap directory in server storage
const PCAP_PATH = 'app/public/pcaps/';
// Path to preinstalled apps list file
const PRE_INSTALLED_APPS_FILE = 'scripts/pre_installed_apps.txt';
// Path to tmp_xapk directory in server storage
const TMP_XAPK_PATH = 'app/public/tmp_xapk/';

class CreateHash {

    protected array $files = [];
    protected array $hashes = [];
    protected array $hash_types = [];
    protected string $process_id;
    protected string $ip_address;
    protected HashProcessData $hash_process_data;

    public function __construct($hash_types, $input_type, $process_id, $ip_address, $channel_id, $process_name, $api_id) {

        $this->hash_types = $hash_types;
        $this->process_id = $process_id;
        $this->ip_address = $ip_address;
        $this->hash_process_data = new HashProcessData($process_id, $input_type, $ip_address, $channel_id, $process_name, $api_id);
    }

    /**
     * @brief The function ensures adding new files to file list
     * @param string $name File name
     * @param string $type File type
     * @param string $path File path
     * @return void
     */
    protected function addFileToFiles(string $name, string $type, string $path) : void {

        $file = [
            'name' => $name,
            'type' => $type,
            'path' => $path,
        ];

        $this->files[] = $file;
    }

    /**
     * @brief The function ensures pcap file creation
     * @param Emulator $emulator Selected emulator
     * @param string $pcap_file_name Output pcap file name
     * @param string $package_name Application package name
     * @return string Out pcap file path
     * @throws CloseAppFailException
     * @throws CreateCommunicationOnEmulatorException
     * @throws PackageNameNotFoundException
     * @throws RunAppFailException
     */
    public function createPcapFile(Emulator $emulator, string $pcap_file_name, string $package_name) : string {

        $pcap_out_path = storage_path(PCAP_PATH).$pcap_file_name;

        $this->addFileToFiles($pcap_file_name, 'PCAP', $pcap_out_path);

        $command = "tshark -i ".$emulator->network_interface." -F pcap -w ".$pcap_out_path;

        $process = Process::fromShellCommandline($command);
        $process->start();

        for($index = 0; $index < env("ANALYSIS_COUNT", 10); $index ++){
            $this->runAppOnEmulator($emulator, $package_name);
            $this->createCommunicationOnEmulator($emulator, $package_name);
            sleep(env("NETWORK_ANALYSIS_TIME", 10));
            $this->closeAppOnEmulator($emulator, $package_name);
        }

        $process->stop(0.2);

        return $pcap_out_path;
    }

    /**
     * @brief The function ensures hashes creation
     * @param string $pcap_file_name Input pcap file name
     * @param string $pcap_file_path Input pcap file path
     * @return array Hashes list
     * @throws HashGeneratorFailException
     */
    public function createHashes(string $pcap_file_name, string $pcap_file_path): array {

        $command = env("PYTHON_COMMAND", "python3")." ".base_path(HASH_SCRIPT_PATH);
        $command = $command." ".$pcap_file_path;
        
        // Add custom generators as second argument if any are specified
        if (!empty($this->hash_types)) {
            $custom_generators = array_filter($this->hash_types, function($type) {
                return strpos($type, 'CUSTOM_') === 0;
            });
            
            if (!empty($custom_generators)) {
                $command = $command." ".escapeshellarg(json_encode($custom_generators));
            }
        }

        $process = Process::fromShellCommandline($command);
        
        // Set environment variables for Python script
        $process->setEnv([
            'LARAVEL_BASE_URL' => 'http://localhost:8000',
            'LARAVEL_API_KEY' => 'python_hash_generator_key_' . env('APP_KEY', 'default_key'),
            'USE_MANUAL_CONFIG' => 'false'
        ]);
        
        // Set timeout to 300 seconds (5 minutes) for Python script execution
        $process->setTimeout(300);
        
        $process->run();

        if (!$process->isSuccessful()) {
            throw new HashGeneratorFailException($process->getErrorOutput());
        }

        return json_decode($process->getOutput());
    }

    /**
     * @brief The function ensures starting app on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws RunAppFailException
     */
    private function runAppOnEmulator(Emulator $emulator, string $package_name): void {

        $command = 'adb shell monkey -p '.trim($package_name).' -c android.intent.category.LAUNCHER 1';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('Run app command: {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RunAppFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief The function ensures application events generation
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws CreateCommunicationOnEmulatorException
     */
    private function createCommunicationOnEmulator(Emulator $emulator, string $package_name): void {

       $command = 'adb shell monkey -p ' . escapeshellarg(trim($package_name)) .
        ' --ignore-crashes' .
        ' --ignore-timeouts' .
        ' --ignore-security-exceptions' .
        ' --monitor-native-crashes' .
        ' --throttle 200' .
        ' --pct-syskeys 0' .
        ' --pct-appswitch 0' .
        ' --pct-anyevent 0' .
        ' -v -v -v 500';


        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('Run app command: {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new CreateCommunicationOnEmulatorException($process->getErrorOutput());
        }
    }

    /**
     * @brief The function ensures closing app on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws CloseAppFailException
     */
    private function closeAppOnEmulator(Emulator $emulator, string $package_name) : void {
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);

        $command = 'adb shell pm clear '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('ADB CLEAR APP command {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('ADB CLEAR APP failed: {error}', ['error' => $process->getErrorOutput()]);
            throw new CloseAppFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief TODO
     * @param string $file_path Path to apk file intended for analysis
     * @return bool
     */
    private function isXAPK(string $file_path) : bool {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'xapk';
    }

    /**
     * @brief TODO
     * @param string $file_path Path to apk file intended for analysis
     * @return bool
     */
    private function isAPK(string $file_path) : bool {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'apk';
    }

    /**
     * @brief Ensures ADB server is running and devices are available
     * @param Emulator $emulator
     * @return void
     * @throws AppInstallationFailException
     */
    private function ensureAdbServerRunning(Emulator $emulator) : void {
        // Start ADB server
        $startCommand = 'adb start-server';
        if (env("ENVIRONMENT", "local") == "server"){
            $startCommand = 'docker exec '.$emulator->name.' '.$startCommand;
        }
        
        $startProcess = Process::fromShellCommandline($startCommand);
        $startProcess->setTimeout(30);
        $startProcess->run();
        
        Log::channel('devlog')->info('ADB START-SERVER command {command}', ['command' => $startCommand]);
        
        // Wait a moment for ADB to initialize
        sleep(3);
        
        // Check if devices are available
        $devicesCommand = 'adb devices';
        if (env("ENVIRONMENT", "local") == "server"){
            $devicesCommand = 'docker exec '.$emulator->name.' '.$devicesCommand;
        }
        
        $devicesProcess = Process::fromShellCommandline($devicesCommand);
        $devicesProcess->setTimeout(30);
        $devicesProcess->run();
        
        Log::channel('devlog')->info('ADB DEVICES command {command}', ['command' => $devicesCommand]);
        Log::channel('devlog')->info('ADB DEVICES output: {output}', ['output' => $devicesProcess->getOutput()]);
        
        if (!$devicesProcess->isSuccessful()) {
            throw new AppInstallationFailException('Failed to check ADB devices: ' . $devicesProcess->getErrorOutput());
        }
        
        $output = $devicesProcess->getOutput();
        if (strpos($output, 'device') === false && strpos($output, 'emulator') === false) {
            throw new AppInstallationFailException('No devices/emulators found. ADB output: ' . $output);
        }
    }

    /**
     * @brief TODO
     * @param Emulator $emulator
     * @param string $file_path Path to apk file intended for analysis
     * @return void
     * @throws AppInstallationFailException
     */
    private function installAPK(Emulator $emulator, string $file_path) : void {
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);
        
        $command = 'adb install '.$file_path;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('ADB INSTALL command {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('ADB INSTALL failed: {error}', ['error' => $process->getErrorOutput()]);
            throw new AppInstallationFailException($process->getErrorOutput());
        }
    }

    protected function extractXAPKfile(string $file_path) : string {
        Log::channel('devlog')->info('FILE PATH {path}', ['path' => $file_path]);
        $xapk_file = Storage::path($file_path);
        Log::channel('devlog')->info('XAPK FILE PATH {path}', ['path' => $xapk_file]);

        // create tmp folder
        $tmp_folder_name = uniqid() . pathinfo($xapk_file, PATHINFO_FILENAME);
        $xapk_unzipped_path = storage_path(TMP_XAPK_PATH . $tmp_folder_name);

        // Unzip the .xapk file
        $zip = new ZipArchive;
        if ($zip->open($xapk_file) === TRUE) {
            $zip->extractTo($xapk_unzipped_path);
            $zip->close();
        } else {
            Storage::deleteDirectory($xapk_unzipped_path);
            throw new XapkFileExtractException('Failed to unzip .xapk file!');
        }

        return $xapk_unzipped_path;
    }


    /**
     * @brief TODO
     * @param Emulator $emulator
     * @param string $file_path Path to apk file intended for analysis
     * @return void
     * @throws AppInstallationFailException
     */
    private function installXAPK(Emulator $emulator, string $xapk_folder_path) : void {
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);

        // Ensure the folder path has a trailing slash
        $folder_path = rtrim($xapk_folder_path, '/') . '/';

        // Get all .apk files from the unzipped directory
        $apk_files = glob($folder_path . '*.apk');

        if (empty($apk_files)) {
            throw new AppInstallationFailException('No APK files found in the .xapk package!');
        }

        // update path for emulator
        $apk_files = array_map(function ($path) {
            return preg_replace('~^.*(?=/storage)~', '/mnt', $path);
        }, $apk_files);

        Log::channel('devlog')->info('APK FILES FROM XAPK: {xapk_files}', ['xapk_files' => $apk_files]);

        // Use adb install-multiple for multiple APK files
        $static_part_command = ['docker', 'exec', $emulator->name, 'adb', 'install-multiple'];
        $command = array_merge($static_part_command, $apk_files);
        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('XAPK INSTALL failed: {error}', ['error' => $process->getErrorOutput()]);
            throw new AppInstallationFailException('Failed to install APKs: ' . $process->getErrorOutput());
        }
    }


    /**
     * @brief The function ensures app installation on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $input_file_path
     * @return void
     * @throws AppInstallationFailException
     */
    protected function installAppOnEmulator(Emulator $emulator, string $input_path, string $input_type) : void {

        if ($input_type == "XAPK") {
            // Handle .xapk installation
            $this->installXAPK($emulator, $input_path);
        } elseif ($input_type == "APK") {
            // Handle .apk installation
            $this->installAPK($emulator, $input_path);
        }
    }

    /**
     * @brief The function ensures app uninstallation on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws AppUninstallationFailException
     */
    protected function uninstallAppOnEmulator(Emulator $emulator, string $package_name) : void {

        $command = 'adb uninstall '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppUninstallationFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief The function ensures getting an application package name
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application package name
     * @throws PackageNameNotFoundException
     */
    protected function getAppPackageName(Emulator $emulator, string $apk_file_path) : string {

        $command = "aapt dump badging ".trim($apk_file_path)." | grep 'package: name' | awk -F \"'\" '{print $2}'";

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('aapt command: {command}', ['package_name' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PackageNameNotFoundException($process->getErrorOutput());
        }

        Log::channel('devlog')->info('package name: {package_name}', ['package_name' => $process->getOutput()]);

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting an application version
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application version
     * @throws AppVersionNotFoundException
     */
    protected function getAppVersionName(Emulator $emulator, string $apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path);
        $command = $command.' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppVersionNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting an application name
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application name
     * @throws AppNameNotFoundException
     */
    protected function getAppName(Emulator $emulator, string $apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path).' | sed -n "s/^application-label:\'\(.*\)\'/\1/p"';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppNameNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting preinstalled apps
     * @return array Preinstalled apps package names
     * @throws LoadingPreinstalledAppsFailed
     */
    protected function getPreInstalledApps(): array {

        try {
            $file = base_path(PRE_INSTALLED_APPS_FILE);
            $packages = [];

            $file_handle = fopen($file, "r");

            if($file_handle){

                while(($line = fgets($file_handle)) !== false){
                    $packages[] = trim($line);
                }

                fclose($file_handle);
                $response = [];

                foreach($packages as $package){
                    $response[] = str_replace("package:", "", $package);
                }

                return $response;
            }else {
                throw new Exception("Error opening file!");
            }

        } catch(Exception $e){
            throw new LoadingPreinstalledAppsFailed($e);
        }
    }

    /**
     * @brief The function ensures saving hashes to database
     * @param array $data Database data
     * @return void
     */
    protected function saveHashes(array $data) : void {

        $process = ProcessModel::where('job_id', '=', $this->process_id)->first();
        
        if (!$process) {
            Log::channel('devlog')->error('Process not found for job_id: {job_id}', ['job_id' => $this->process_id]);
            throw new Exception('Process not found for job_id: ' . $this->process_id);
        }
        
        $process_id = $process->id;

        $identifier = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        $new_application = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        $application = Application::firstOrCreate($identifier, $new_application);

        foreach($this->files as $file){
           $db_file = File::create([
                'name' => $file['name'],
                'type' => $file['type'],
                'path' => $file['path'],
                'app_id' => $application->id,
           ]);
           $db_file->save();
        }

        foreach($data["hashes"] as $hash){

            Log::channel('devlog')->info('Hashes : {name}', ['name' => $hash]);
            Log::channel('devlog')->info('SNI Flag: {flag}, Is Flagged: {flagged}', [
                'flag' => $hash->sni_flag ?? 'null',
                'flagged' => $hash->is_flagged ?? 'null'
            ]);

            // Extract custom hashes from the hash object
            $custom_hashes = [];
            foreach($hash as $key => $value) {
                if(strpos($key, 'custom_') === 0) {
                    $custom_hashes[$key] = $value;
                }
            }

            $new_record = [
                'app_id' => $application->id,
                'process_id' => $process_id,
                'ja3_hash' => $hash->ja3_hash,
                'ja3s_hash' => $hash->ja3s_hash,
                'sni' => $hash->sni,
                'sni_flag' => isset($hash->sni_flag) ? $hash->sni_flag : null,
                'is_flagged' => isset($hash->is_flagged) ? (bool)$hash->is_flagged : false,
                'ja4_hash' => $hash->ja4_hash,
                'ja4s_hash' => $hash->ja4s_hash,
                'ja4x_hash' => json_encode($hash->ja4x_hash),
                'custom_hashes' => !empty($custom_hashes) ? json_encode($custom_hashes) : null,
                'ip_src' => $hash->ip_src,
                'port_src' => $hash->port_src,
                'ip_dest' => $hash->ip_dest,
                'port_dest' => $hash->port_dest,
            ];
            
            Log::channel('devlog')->info('Saving hash with flag: {flag}, flagged: {flagged}', [
                'flag' => $new_record['sni_flag'],
                'flagged' => $new_record['is_flagged']
            ]);

            $db_hash = Hash::create($new_record);
            $db_hash->save();
        }
    }

    /**
     * @brief The function ensures deleting APK file after hash creation
     * @param string $file_path APK file path
     * @return void
     */
    protected function delete_apk_file(string $file_path): void {

        $relative_path = substr($file_path,
            strpos($file_path, '/storage/app') + strlen('/storage/app'));

        if (Storage::exists($relative_path)) {
            Storage::delete($relative_path);
            DB::table('files')->where('files.path', '=', $file_path)->delete();
        }
    }

    /**
     * @brief The function ensures the getting free emulator from database
     * @return mixed
     */
    protected function get_free_emulator(): mixed {
        return Emulator::where("is_working", "=", false)->first();
    }

    /**
     * @brief The function ensures the setting emulator state to database
     * @param Emulator $emulator Emulator data object
     * @param bool $state New emulator state
     * @return void
     */
    protected function set_emulator_working_state(Emulator $emulator, bool $state): void {
        Emulator::where("name",$emulator->name)->update(["is_working" => $state]);
    }
}
