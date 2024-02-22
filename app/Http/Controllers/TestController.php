<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class TestController extends Controller
{
    //


    public function install(){

        $apk_file_path = "/mnt/storage/app/public/uploads/apk_inserted/125920_Alza_10.15.1_Apkpure.apk";
        $command = 'adb install '.$apk_file_path;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();

    }

    public function run(){
        $command = 'adb shell monkey -p cz.alza.eshop -c android.intent.category.LAUNCHER 1';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);

        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();
    }

    public function close(){

        $package_name = "cz.alza.eshop";
        $command = 'adb shell pm clear '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();
    }

    public function uninstall() {

        $package_name = "cz.alza.eshop";
        $command = 'adb uninstall '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();

    }

    public function python(){

        $this->install();
        
        $pcap_out_path = "/home/xbwolf02/pcaps/novy2.pcap";

        $command = "tshark -i ".env("NETWORK_INTERFACE", "en0")." -F pcap -w ".$pcap_out_path;

        $process = Process::fromShellCommandline($command);
        $process->start();

        $this->run();
        sleep(20);
        $this->close();

        $process->stop();
    }

    protected function getAppPackageName() : string {

        $apk_file_path = "/mnt/storage/app/public/uploads/apk_inserted/125920_Alza_10.15.1_Apkpure.apk";

        $command = "aapt dump badging ".trim($apk_file_path)." | grep \"package: name\" | awk -F \"'\" '{print $2}'";

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();
    }

    protected function getAppVersionName() : string {

        $apk_file_path = "/mnt/storage/app/public/uploads/apk_inserted/125920_Alza_10.15.1_Apkpure.apk";

        $command = 'aapt dump badging '.trim($apk_file_path).' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();
    }

    /**
     * @param $apk_file_path
     * @return string
     */
    protected function getAppName() : string {

        $apk_file_path = "/mnt/storage/app/public/uploads/apk_inserted/125920_Alza_10.15.1_Apkpure.apk";

        $command = 'aapt dump badging '.trim($apk_file_path).' | sed -n "s/^application-label:\'\(.*\)\'/\1/p"';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.env("EMULATOR_NAME", null).' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return "ERR: ".$process->getErrorOutput();
        }

        return $process->getOutput();
    }

}
