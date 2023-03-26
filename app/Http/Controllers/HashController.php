<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

const APK_INSERTED_DIR = './storage/uploads/apk_inserted/';
const APK_DOWNLOADED_DIR = './storage/uploads/apk_downloaded/';
const JA3_HASH_SCRIPT_PATH = '../scripts/AnalyzePcapFile.py';
const PCAP_PATH = './storage/pcaps/';


class HashController extends Controller {

    private array $ip_black_list = [
    "142.251.37.106",
    "142.251.36.138",
    "216.239.32.16",
    ];

    private string $app_package_name;
    private string $app_version_name;

    /**
     * @param $pcap_file_name
     * @param $apk_path
     * @return string
     */
    public function createPcapFile($pcap_file_name, $apk_path) : string {

        $package_name = trim($this->getAppPackageName($apk_path));
        $pcap_out_path = PCAP_PATH.$pcap_file_name;

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
        $ja3_pcap_path =  $this->applyPcapFilter($pcap_file_path, $pcap_file_name, 'JA3');
        $process = new Process(['python3', JA3_HASH_SCRIPT_PATH, $ja3_pcap_path]);
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

        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));

        $this->installAppOnEmulator($apk_path);
        $pcap_file_path = $this->createPcapFile($pcap_file_name, $apk_path);
        $this->uninstallAppOnEmulator($package_name);


        if(in_array('JA3', $hash_types )){
            $ja3_hashes = $this->createJA3hash($pcap_file_path, $pcap_file_name);
            $hashes['JA3'] = $ja3_hashes;
        }

        $results = [
            'apk_name' => $apk_file_name,
            'package_name' => $package_name,
            'version_name' => $version_name,
            'hashes' => $hashes,
        ];

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

        if($hash_type == 'JA3')
            $new_pcap_file_path = PCAP_PATH.'JA3_'.$pcap_file_name;
        else if($hash_type == 'JA3S')
            $new_pcap_file_path = PCAP_PATH.'JA3S_'.$pcap_file_name;

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

}
