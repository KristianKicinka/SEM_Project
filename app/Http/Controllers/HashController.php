<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

const APK_INSERTED_DIR = './storage/uploads/apk_inserted/';
const APK_DOWNLOADED_DIR = './storage/uploads/apk_downloaded/';
const JA3_JA3S_HASH_SCRIPT_PATH = '../scripts/JA3_JA3S_hash_generator.py';
const PCAP_PATH = './storage/pcaps/';


class HashController extends Controller {

    private array $ip_black_list = [
    "142.251.37.106",
    "142.251.36.138",
    "216.239.32.16",
    ];

    private string $app_package_name;
    private string $app_version_name;
    private array $files = [];


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
        $pcap_out_path = PCAP_PATH.$pcap_file_name;

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
        $process = new Process(['python3', JA3_JA3S_HASH_SCRIPT_PATH, $JA3_pcap_path, 'JA3']);
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
        $process = new Process(['python3', JA3_JA3S_HASH_SCRIPT_PATH, $JA3S_pcap_path, 'JA3S']);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('Hash creation failed!');
        }

        return $this->parseAnalysisOutput($process->getOutput());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHash(Request $request){

        $apk_file_name = $request->get('file_name');
        $pcap_file_name = str_replace('.apk', '.pcap', $apk_file_name);
        $hash_types = $request->get('hash_types');
        $hashes = [];

        if($request->get('apk_type') == 'inserted')
            $apk_path = APK_INSERTED_DIR.$apk_file_name;
        else if($request->get('apk_type') == 'downloaded')
            $apk_path = APK_DOWNLOADED_DIR.$apk_file_name;

        $this->addFileToFiles($apk_file_name, 'APK', $apk_path);

        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));
        $application_name = trim($this->getAppName($apk_path));

        $this->installAppOnEmulator($apk_path);
        $pcap_file_path = $this->createPcapFile($pcap_file_name, $apk_path);
        $this->uninstallAppOnEmulator($package_name);


        if(in_array('JA3', $hash_types)){
            $JA3_hashes = $this->createJA3hash($pcap_file_path, $pcap_file_name);
            $hashes['JA3'] = $JA3_hashes;
        }

        if(in_array('JA3S', $hash_types)){
            $JA3S_hashes = $this->createJA3Shash($pcap_file_path, $pcap_file_name);
            $hashes['JA3S'] = $JA3S_hashes;
        }

        $results = [
            'app_name' => $application_name,
            'package_name' => $package_name,
            'app_version' => $version_name,
            'hashes' => $hashes,
        ];

        $this->saveResultsToDatabase($results);

        return response()->json($results);
    }

    /**
     * @param $type
     * @return string
     */
    private function createFilter($type): string {
        $filter = "";

        if($type == 'JA3')
            $filter = "tls.handshake.type==1 && tcp && !(";
        else if($type == 'JA3S')
            $filter = "tls.handshake.type==2 && tcp && !(";

        $index = 0;
        foreach ($this->ip_black_list as $ip){
            $filter = $filter." ip.dst==".$ip;
            $index++;
            if($index != count($this->ip_black_list))
                $filter = $filter." or";
        }

        return $filter.")";
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
            $new_pcap_file_path = PCAP_PATH.'JA3_'.$pcap_file_name;
            $this->addFileToFiles('JA3_'.$pcap_file_name, 'PCAP', $new_pcap_file_path);
        }
        else if($hash_type == 'JA3S'){
            $new_pcap_file_path = PCAP_PATH.'JA3S_'.$pcap_file_name;
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

    private function saveResultsToDatabase($results){

        //Log::channel('devlog')->info('saving started');

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

        Log::channel('devlog')->info('file db created');

        foreach($results['hashes'] as $hash_type => $hashes){
            foreach($hashes as $hash){
                $hash = Hash::create([
                    'app_id' => $application->id,
                    'hash_type' => $hash_type,
                    'hash' => $hash,
                ]);
                $hash->save();
            }
        }

        
    }

    public function createHashAPI(Request $request){
        $response = [
            'app' => $request->input('app'),
            'types' => $request->input('types'),
        ];

        return response()->json($response);
    }

}
