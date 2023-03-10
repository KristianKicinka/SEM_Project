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

    public function createPcapFile($apk_name, $apk_path){

        $package_name = trim($this->getAppPackageName($apk_path));
        $file_name = str_replace('.apk', '.pcap', $apk_name);
        $pcap_out_path = "./storage/pcaps/".$file_name;

        $pcap_process = new Process(['tshark','-i','en0','-F','pcap','-w',$pcap_out_path]);
        $pcap_process->start();

        $this->runAppOnEmulator($package_name);
        sleep(10);
        $this->closeAppOnEmulator($package_name);

        $pcap_process->stop();

        $print = $this->applyPcapFilter($pcap_out_path);

        return $print;
    }

    public function createHash(Request $request){

        $file_name = $request->get('file_name');
        $apk_path = "./storage/uploads/apk/".$file_name;

        $package_name = trim($this->getAppPackageName($apk_path));
        $version_name = trim($this->getAppVersionName($apk_path));

        $this->installAppOnEmulator($apk_path);

        $print = $this->createPcapFile($file_name, $apk_path);

        $this->uninstallAppOnEmulator($package_name);

    
        return response()->json($print);

    }

    private function createFilter(): string {

        $filter = "tls.handshake.type==1 && tcp && !(";
        $index = 0;
        foreach ($this->ip_black_list as $ip){
            $filter = $filter." ip.dst==".$ip;
            $index++;
            if($index != count($this->ip_black_list))
                $filter = $filter." or";
        }

        return $filter.")";
    }

    private function applyPcapFilter($file_path){

        $filter = $this->createFilter();
        $command = 'tshark -r '.$file_path.' -Y "'.$filter.'" -w '.$file_path;
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('Apply pcap filter failed!');
        }

    }

    private function runAppOnEmulator($package_name){

        $command = 'adb shell monkey -p '.$package_name.' -c android.intent.category.LAUNCHER 1';

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App installing error!');
        }
    }

    private function closeAppOnEmulator($package_name){

        $command = 'adb shell pm clear '.$package_name;

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

    private function uninstallAppOnEmulator($package_name){
        $process = new Process(['adb', 'uninstall', $package_name]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('App uninstalling error!');
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

}
