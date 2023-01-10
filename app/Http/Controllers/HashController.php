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

    public function createPcapFile(){

        $filter = $this->createFilter();
        $pcap_out_name = "messenger";
        $pcap_out_path = "../../../scripts/pcap/".$pcap_out_name;
        $tshark_command = 'tshark -F pcap -Y "'.$filter.'" -w '.$pcap_out_path;
    }

    public function createHash(){

        $this->createPcapFile();

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

        $run_app_process = new Process([$command]);
        $run_app_process->run();

    }

    private function closeAppOnEmulator($package_name){
        $command = 'adb shell am force-stop '.$package_name;

        $stop_app_process = new Process([$command]);
        $stop_app_process->run();
    }

    private function installAppOnEmulator($apk_file_path){
        $install_app_process = new Process(['adb', 'install', $apk_file_path]);
        $install_app_process->run();

        if (!$install_app_process->isSuccessful()) {
            return "Installing app error!";
        }

    }

    private function uninstallAppOnEmulator($package_name){
        //TODO Create uninstall function
    }


}
