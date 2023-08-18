<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

use App\Objects\ProcessData;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use App\Models\Process as ProcessModel;

const APK_INSERTED_DIR = 'app/public/uploads/apk_inserted/';
const APK_DOWNLOADED_DIR = 'app/public/uploads/apk_downloaded/';
const HASH_SCRIPT_PATH = 'scripts/hash_generator.py';
const PCAP_PATH = 'app/public/pcaps/';


class CreateHash implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $files = [];

    private string $apk_file_name;
    private string $pcap_file_name;
    private array $hash_types;
    private string $apk_path;
    private string $frontend_id;
    private $ip_address;
    private ProcessData $process_data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file_name, $hash_types, $input_type, $frontend_id, $ip_address){
        $this->apk_file_name = $file_name;
        $this->pcap_file_name = str_replace('.apk', '.pcap', $file_name);
        $this->hash_types = $hash_types;
        $this->frontend_id = $frontend_id;
        $this->ip_address = $ip_address;
        $this->process_data = new ProcessData($frontend_id, $input_type, $ip_address);
        $this->setApkPath($input_type);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        $hashes = [];

        $this->process_data->setProcessing();
        $this->process_data->nextProcessPart();
        $this->addFileToFiles($this->apk_file_name, 'APK', $this->apk_path);
        
        $package_name = trim($this->getAppPackageName($this->apk_path));
        $version_name = trim($this->getAppVersionName($this->apk_path));
        $application_name = trim($this->getAppName($this->apk_path));

        $this->process_data->nextProcessPart();
        $this->installAppOnEmulator($this->apk_path);
        
        $this->process_data->nextProcessPart();
        $pcap_file_path = $this->createPcapFile($this->pcap_file_name, $this->apk_path);
        
        $this->uninstallAppOnEmulator($package_name);

        $this->process_data->nextProcessPart();

        if(in_array('JA3', $this->hash_types)){
            $JA3_hashes = $this->createJA3hash($pcap_file_path, $this->pcap_file_name);
            $hashes['JA3'] = $JA3_hashes;
        }

        if(in_array('JA3S', $this->hash_types)){
            $JA3S_hashes = $this->createJA3Shash($pcap_file_path, $this->pcap_file_name);
            $hashes['JA3S'] = $JA3S_hashes;
        }

        $results = [
            'app_name' => $application_name,
            'package_name' => $package_name,
            'app_version' => $version_name,
            'hashes' => $hashes,
        ];

        $this->process_data->nextProcessPart();
        $this->saveHashes($results);
        $this->process_data->setFinished();
    }

    private function setApkPath($type){
        if($type == 'apk_file')
            $this->apk_path = storage_path(APK_INSERTED_DIR).$this->apk_file_name;
        else if($type == 'app_name')
            $this->apk_path = storage_path(APK_DOWNLOADED_DIR).$this->apk_file_name;
    }

    private function addFileToFiles($name, $type, $path){
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

        $pcap_process = new Process(['tshark','-i','en0','-F','pcap','-w',$pcap_out_path]);
        $pcap_process->start();

        $this->runAppOnEmulator($package_name);
        sleep(10);
        $this->closeAppOnEmulator($package_name);

        $pcap_process->stop();

        return $pcap_out_path;
    }

    /**
     * @param $output
     * @return array
     */
    private function parseAnalysisOutput($output) : array {

        $regex = '/\'[0-9a-zA-Z]*\'/m';
        preg_match_all($regex, $output, $hashes, PREG_SET_ORDER, 0);

        foreach($hashes as $key => $value) {
            $hashes[$key] = str_replace('\'','',$value[0]);
        }

        return $hashes;
    }

    /**
     * @param $pcap_file_path
     * @param $pcap_file_name
     * @return array|JsonResponse
     */
    private function createJA3hash($pcap_file_path, $pcap_file_name) {
        $JA3_pcap_path =  $this->applyPcapFilter($pcap_file_path, $pcap_file_name, 'JA3');
        $process = new Process(['python3', base_path(HASH_SCRIPT_PATH), $JA3_pcap_path, 'JA3']);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('Hash creation failed!');
        }

        return $this->parseAnalysisOutput($process->getOutput());
    }

     /**
     * @param $pcap_file_path
     * @param $pcap_file_name
     * @return array|JsonResponse
     */
    private function createJA3Shash($pcap_file_path, $pcap_file_name){
        $JA3S_pcap_path =  $this->applyPcapFilter($pcap_file_path, $pcap_file_name, 'JA3S');
        $process = new Process(['python3', base_path(HASH_SCRIPT_PATH), $JA3S_pcap_path, 'JA3S']);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('Hash creation failed!');
        }

        return $this->parseAnalysisOutput($process->getOutput());
    }

    /**
     * @param $type
     * @return string
     */
    private function createFilter($type): string {
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
        $command = 'tshark -r '.$pcap_file_path.' -Y "'.$filter.'" -w '.$new_pcap_file_path;
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('Apply pcap filter failed!');
        }

        return $new_pcap_file_path;
    }

    /**
     * @param $package_name
     * @return JsonResponse|void
     */
    private function runAppOnEmulator($package_name) {

        $command = 'adb shell monkey -p '.$package_name.' -c android.intent.category.LAUNCHER 1';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App installing error!');
        }
    }

    /**
     * @param $package_name
     * @return void
     */
    private function closeAppOnEmulator($package_name) : void {

        $command = 'adb shell pm clear '.$package_name;

        $stop_app_process = Process::fromShellCommandline($command);
        $stop_app_process->run();
    }

    /**
     * @param $apk_file_path
     * @return JsonResponse|void
     */
    private function installAppOnEmulator($apk_file_path){
        $process = new Process(['adb', 'install', $apk_file_path]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App installing error!');
        }

    }

    /**
     * @param $package_name
     * @return JsonResponse|void
     */
    private function uninstallAppOnEmulator($package_name){
        $process = new Process(['adb', 'uninstall', $package_name]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App uninstalling error!');
        }

    }

    /**
     * @param $apk_file_path
     * @return JsonResponse|string
     */
    private function getAppPackageName($apk_file_path){

        $command = 'aapt dump badging '.$apk_file_path.' | grep package | awk \'{print $2}\' | sed s/name=//g | sed s/\\\'//g';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App pcakage name error!');
        }

        return $process->getOutput();
    }

    /**
     * @param $apk_file_path
     * @return JsonResponse|string
     */
    private function getAppVersionName($apk_file_path){

        $command = 'aapt dump badging '.$apk_file_path.' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App version name error!');
        }

        return $process->getOutput();
    }

    private function getAppName($apk_file_path){
        $command = 'aapt dump badging '.$apk_file_path.' | sed -n "s/^application-label:\'\(.*\)\'/\1/p"';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App name error!');
        }

        return $process->getOutput();
    }

    private function saveHashes($results){

        $process_id = ProcessModel::where('frontend_id', '=', $this->frontend_id)->first()->id;

        $application = Application::create([
            'name' => $results['app_name'],
            'package_name' => $results['package_name'],
            'version' => $results['app_version'],
        ]);

        $application->save();

        foreach($this->files as $file){
           $db_file = File::create([
                'name' => $file['name'],
                'type' => $file['type'],
                'path' => $file['path'],
                'app_id' => $application->id,
           ]);
           $db_file->save();
        }

        foreach($results['hashes'] as $hash_type => $hashes){
            foreach($hashes as $hash){

                $identifier = [
                    'app_id' => $application->id,
                    'hash' => $hash,
                    'hash_type' => $hash_type,
                ];

                $new_record = [
                    'app_id' => $application->id,
                    'process_id' => $process_id,
                    'hash' => $hash,
                    'hash_type' => $hash_type,
                ];
                
                Hash::firstOrCreate($identifier, $new_record);
            }
        }        
    }

}
