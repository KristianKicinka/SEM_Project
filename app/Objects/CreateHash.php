<?php

namespace App\Objects;

use App\Exceptions\AppInstalationFailException;
use App\Exceptions\AppNameNotFoundException;
use App\Exceptions\AppUninstalationFailException;
use App\Exceptions\AppVersionNotFoundException;
use App\Exceptions\CloseAppFailException;
use App\Exceptions\HashGeneratorFailException;
use App\Exceptions\PackageNameNotFoundException;
use App\Exceptions\PcapFileNotFoundException;
use App\Exceptions\RunAppFailException;
use App\Objects\ProcessData;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use App\Models\Process as ProcessModel;
use Illuminate\Support\Facades\Log;

use Symfony\Component\Process\Process;

const HASH_SCRIPT_PATH = 'scripts/hash_generator.py';
const PCAP_PATH = 'app/public/pcaps/';

class CreateHash {

    protected array $files = [];
    protected array $hashes = [];
    protected array $hash_types = [];

    protected string $job_id;
    protected string $ip_address;

    protected HashProcessData $hash_process_data;

    public function __construct($hash_types, $input_type, $job_id, $ip_address) {

        $this->hash_types = $hash_types;
        $this->job_id = $job_id;
        $this->ip_address = $ip_address;
        $this->hash_process_data = new HashProcessData($job_id, $input_type, $ip_address);
    }

    /**
     * @param $name
     * @param $type
     * @param $path
     * @return void
     */
    protected function addFileToFiles($name, $type, $path) : void {

        $file = [
            'name' => $name,
            'type' => $type,
            'path' => $path,
        ];

        array_push($this->files, $file);
    }

    /**
     * @param $pcap_file_name
     * @param $apk_path
     * @return string
     */
    public function createPcapFile($pcap_file_name, $apk_path) : string {

        $package_name = trim($this->getAppPackageName($apk_path));
        $pcap_out_path = storage_path(PCAP_PATH).$pcap_file_name;

        $this->addFileToFiles($pcap_file_name, 'PCAP', $pcap_out_path);

        $command = "tshark -i ".env("NETWORK_INTERFACE", "en0")." -F pcap -w ".$pcap_out_path;

        $process = Process::fromShellCommandline($command);
        $process->start();

        $this->runAppOnEmulator($package_name);
        sleep(env("NETWORK_ANALYSIS_TIME", 30));
        $this->closeAppOnEmulator($package_name);

        $process->stop();

        return $pcap_out_path;
    }

    /**
     * @param $pcap_file_path
     * @param $pcap_file_name
     * @return array
     */
    private function createJA3hash($pcap_file_path, $pcap_file_name) : array {

        //$JA3_pcap_path =  $this->applyPcapFilter($pcap_file_path, $pcap_file_name, 'JA3');

        $command = env("PYTHON_COMMAND", "python3")." ".base_path(HASH_SCRIPT_PATH)." ".$pcap_file_path." JA3";

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new HashGeneratorFailException($process->getErrorOutput());
        }

        $ja3_hashes = json_decode($process->getOutput(), true);

        Log::channel('devlog')->info('JA3 hashes : {hashes}', ['hashes' => $ja3_hashes]);

        return json_decode($process->getOutput());
    }

     /**
     * @param $pcap_file_path
     * @param $pcap_file_name
     * @return array
     */
    private function createJA3Shash($pcap_file_path, $pcap_file_name) : array {

        //$JA3S_pcap_path =  $this->applyPcapFilter($pcap_file_path, $pcap_file_name, 'JA3S');

        $command = env("PYTHON_COMMAND", "python3")." ".base_path(HASH_SCRIPT_PATH)." ".$pcap_file_path." JA3S";

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new HashGeneratorFailException($process->getErrorOutput());
        }

        return json_decode($process->getOutput());
    }

    
    public function createHashes($hash_types, $pcap_file_name, $pcap_file_path){
        $hashes = [];

        if(in_array('JA3', $hash_types)){
            $JA3_hashes = $this->createJA3hash($pcap_file_path, $pcap_file_name);
            $hashes['JA3'] = $JA3_hashes;
        }

        if(in_array('JA3S', $hash_types)){
            $JA3S_hashes = $this->createJA3Shash($pcap_file_path, $pcap_file_name);
            $hashes['JA3S'] = $JA3S_hashes;
        }

        return $hashes;
    }

    /**
     * @param $type
     * @return string
     */
    private function createFilter($type) : string {

        $filter = "";

        if($type == 'JA3')
            $filter = "tls.handshake.type==1 && tcp";
        else if($type == 'JA3S')
            $filter = "tls.handshake.type==2 && tcp";

        return $filter;
    }

    /**
     * @param $pcap_file_path
     * @param $pcap_file_name
     * @param $hash_type
     * @return string
     */
    private function applyPcapFilter($pcap_file_path, $pcap_file_name, $hash_type) : string {
        $new_pcap_file_path = "";

        if($hash_type == 'JA3'){
            $new_pcap_file_path = storage_path(PCAP_PATH).'JA3_'.$pcap_file_name;
            $this->addFileToFiles('JA3_'.$pcap_file_name, 'PCAP', $new_pcap_file_path);
        }
        else if($hash_type == 'JA3S'){
            $new_pcap_file_path = storage_path(PCAP_PATH).'JA3S_'.$pcap_file_name;
            $this->addFileToFiles('JA3S_'.$pcap_file_name, 'PCAP', $new_pcap_file_path);
        }

        $filter = $this->createFilter($hash_type);

        $command = 'tshark -r '.trim($pcap_file_path).' -Y "'.$filter.'" -w '.$new_pcap_file_path;

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PcapFileNotFoundException($process->getErrorOutput());
        }

        return $new_pcap_file_path;
    }

    /**
     * @param $package_name
     * @return JsonResponse|void
     */
    private function runAppOnEmulator($package_name) {

        $command = 'adb shell monkey -p '.trim($package_name).' -c android.intent.category.LAUNCHER 1';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        Log::channel('devlog')->info('Run app command: {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RunAppFailException($process->getErrorOutput());
        }
    }

    /**
     * @param $package_name
     * @return void
     */
    private function closeAppOnEmulator($package_name) : void {

        $command = 'adb shell pm clear '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new CloseAppFailException($process->getErrorOutput());
        }
    }

    /**
     * @param $apk_file_path
     * @return void
     */
    protected function installAppOnEmulator($apk_file_path) : void {

        $command = 'adb install '.$apk_file_path;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        Log::channel('devlog')->info('ADB INSTALL command {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppInstalationFailException($process->getErrorOutput());
        }
    }

    /**
     * @param $package_name
     * @return void
     */
    protected function uninstallAppOnEmulator($package_name) : void {

        $command = 'adb uninstall '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppUninstalationFailException($process->getErrorOutput());
        }
    }

    /**
     * @param $apk_file_path
     * @return string
     */
    protected function getAppPackageName($apk_file_path) : string {

        $command = "aapt dump badging ".trim($apk_file_path)." | grep \"package: name\" | awk -F \"'\" '{print $2}'";

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
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
     * @param $apk_file_path
     * @return string
     */
    protected function getAppVersionName($apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path).' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppVersionNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @param $apk_file_path
     * @return string
     */
    protected function getAppName($apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path).' | sed -n "s/^application-label:\'\(.*\)\'/\1/p"';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppNameNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    protected function getPreInstlledApps(){

        $command = "docker exec ".env("EMULATOR_NAME", null)." adb shell cmd package list packages";

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        $packages = explode("\n", $process->getOutput());

        $response = [];

        foreach($packages as $package){
            $response[] = str_replace("package:", "", $package);
        }

        return $response;
    }

    /**
     * @param $results
     * @return void
     */
    protected function saveHashes($data) : void {

        $process_id = ProcessModel::where('job_id', '=', $this->job_id)->first()->id;

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

        foreach($data['hashes'] as $hash_type => $hashes){
            foreach($hashes as $hash){

                Log::channel('devlog')->info('Hashes : {name}', ['name' => $hash]);

                /*
                $identifier = [
                    'app_id' => $application->id,
                    'hash' => $hash,
                    'hash_type' => $hash_type,
                ];*/

                $new_record = [
                    'app_id' => $application->id,
                    'process_id' => $process_id,
                    'hash' => $hash->hash,
                    'hash_type' => $hash_type,
                    'sni' => $hash->sni
                ];

                $db_hash = Hash::create($new_record);
                $db_hash->save();

                //Hash::firstOrCreate($identifier, $new_record);
            }
        }
    }
}
