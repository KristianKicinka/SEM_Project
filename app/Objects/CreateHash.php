<?php

namespace App\Objects;

use App\Exceptions\AppInstalationFailException;
use App\Exceptions\AppNameNotFoundException;
use App\Exceptions\AppUninstalationFailException;
use App\Exceptions\AppVersionNotFoundException;
use App\Exceptions\CloseAppFailException;
use App\Exceptions\CreateCommunicationOnEmulatorException;
use App\Exceptions\HashGeneratorFailException;
use App\Exceptions\LoadingPreinstalledAppsFailed;
use App\Exceptions\PackageNameNotFoundException;
use App\Exceptions\RunAppFailException;

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

// Hash generator python script path
const HASH_SCRIPT_PATH = 'scripts/hash_generator.py';
// Path to pcap directory in server storage
const PCAP_PATH = 'app/public/pcaps/';
// Path to preinstalled apps list file
const PRE_INSTALLED_APPS_FILE = 'scripts/pre_installed_apps.txt';

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
     * @brief
     * @param string $name
     * @param string $type
     * @param string $path
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
     * @brief
     * @param Emulator $emulator
     * @param string $pcap_file_name
     * @param string $apk_path
     * @return string
     * @throws CloseAppFailException
     * @throws CreateCommunicationOnEmulatorException
     * @throws PackageNameNotFoundException
     * @throws RunAppFailException
     */
    public function createPcapFile(Emulator $emulator, string $pcap_file_name, string $apk_path) : string {

        $package_name = trim($this->getAppPackageName($emulator, $apk_path));
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
     * @brief
     * @param string $pcap_file_path
     * @param string $pcap_file_name
     * @return array
     * @throws HashGeneratorFailException
     */
    private function createHashSniJa3Ja3S(string $pcap_file_path, string $pcap_file_name) : array {

        $command = env("PYTHON_COMMAND", "python3")." ".base_path(HASH_SCRIPT_PATH);
        $command = $command." ".$pcap_file_path;

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new HashGeneratorFailException($process->getErrorOutput());
        }

        return json_decode($process->getOutput());
    }

    /**
     * @brief
     * @param string $pcap_file_name
     * @param string $pcap_file_path
     * @return array
     * @throws HashGeneratorFailException
     */
    public function createHashes(string $pcap_file_name, string $pcap_file_path): array {
        return $this->createHashSniJa3Ja3S($pcap_file_path, $pcap_file_name);
    }

    /**
     * @brief
     * @param Emulator $emulator
     * @param string $package_name
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
     * @param Emulator $emulator
     * @param string $package_name
     * @return void
     * @throws CreateCommunicationOnEmulatorException
     */
    private function createCommunicationOnEmulator(Emulator $emulator, string $package_name): void {

        $command = 'adb shell monkey -p '.trim($package_name).' --ignore-crashes -v 500';

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
     * @brief
     * @param Emulator $emulator
     * @param string $package_name
     * @return void
     * @throws CloseAppFailException
     */
    private function closeAppOnEmulator(Emulator $emulator, string $package_name) : void {

        $command = 'adb shell pm clear '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new CloseAppFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief
     * @param Emulator $emulator
     * @param string $apk_file_path
     * @return void
     * @throws AppInstalationFailException
     */
    protected function installAppOnEmulator(Emulator $emulator, string $apk_file_path) : void {

        $command = 'adb install '.$apk_file_path;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('ADB INSTALL command {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppInstalationFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief
     * @param Emulator $emulator
     * @param string $package_name
     * @return void
     * @throws AppUninstalationFailException
     */
    protected function uninstallAppOnEmulator(Emulator $emulator, string $package_name) : void {

        $command = 'adb uninstall '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppUninstalationFailException($process->getErrorOutput());
        }
    }

    /**
     * @param Emulator $emulator
     * @param string $apk_file_path
     * @return string
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
     * @brief
     * @param Emulator $emulator
     * @param string $apk_file_path
     * @return string
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
     * @brief
     * @param Emulator $emulator
     * @param string $apk_file_path
     * @return string
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
     * @brief
     * @return array
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
     * @brief The function ensures
     * @param array $data
     * @return void
     */
    protected function saveHashes(array $data) : void {

        $process_id = ProcessModel::where('job_id', '=', $this->process_id)->first()->id;

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

            $new_record = [
                'app_id' => $application->id,
                'process_id' => $process_id,
                'ja3_hash' => $hash->ja3_hash,
                'ja3s_hash' => $hash->ja3s_hash,
                'sni' => $hash->sni,
                'ja4_hash' => $hash->ja4_hash,
                'ja4s_hash' => $hash->ja4s_hash,
                'ja4x_hash' => json_encode($hash->ja4x_hash),
                'ip_src' => $hash->ip_src,
                'port_src' => $hash->port_src,
                'ip_dest' => $hash->ip_dest,
                'port_dest' => $hash->port_dest,
            ];

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
