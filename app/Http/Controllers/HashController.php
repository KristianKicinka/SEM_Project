<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;



class HashController extends Controller {

    private array $ip_black_list = [
    "142.251.37.106",
    "142.251.36.138",
    "216.239.32.16",
    ];

    private string $app_package_name;
    private string $app_version_name;

    public function createPcapFile(){

        $filter = $this->createFilter();
        $pcap_out_name = "messenger";
        $pcap_out_path = "../../../scripts/pcap/".$pcap_out_name;
        $tshark_command = 'tshark -F pcap -Y "'.$filter.'" -w '.$pcap_out_path;
    }

    public function createHash(Request $request){

        $apk_path = "./storage/uploads/apk/".$request->get('file_name');

        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));

        //$print = $this->installAppOnEmulator($apk_path);

        $print = $this->uninstallAppOnEmulator($package_name);

        //$this->createPcapFile();
        error_log("Filter created!");

        return response()->json($print);

    }

    private function createFilter(): string {

        $filter = " tls.handshake.type==1 && tcp && !(";
        $index = 0;
        foreach ($this->ip_black_list as $ip){
            $filter = $filter." ip.dst==".$ip;
            $index++;
            if($index != count($this->ip_black_list))
                $filter = $filter." or";
        }

        return $filter.")";
    }

    private function runAppOnEmulator($package_name){

        $command = 'adb shell am start -n '.$package_name.'/'.$package_name.'.MainActivity';

        $process = new Process([$command]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App installing error!');
        }

    }

    private function closeAppOnEmulator($package_name){

        $command = 'adb shell am force-stop '.$package_name;

        $stop_app_process = Process::fromShellCommandline($command);
        $stop_app_process->run();
    }

    private function installAppOnEmulator($apk_file_path){
        $process = new Process(['adb', 'install', $apk_file_path]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App installing error!');
        }

    }

    private function getAppPackageName($apk_file_path){
    
        $command = 'aapt dump badging '.$apk_file_path.' | grep package | awk \'{print $2}\' | sed s/name=//g | sed s/\\\'//g';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App pcakage name error!');
        }
        
        return $process->getOutput();
    }

    private function getAppVersionName($apk_file_path){

        $command = 'aapt dump badging '.$apk_file_path.' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App version name error!');
        }
        
        return $process->getOutput();
        
    }

    private function uninstallAppOnEmulator($package_name){
        $process = new Process(['adb', 'uninstall', $package_name]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App uninstalling error!');
        }

    }


}
