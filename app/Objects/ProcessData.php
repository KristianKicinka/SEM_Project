<?php

namespace App\Objects;

use App\Models\Process as ProcessModel;
use Illuminate\Support\Facades\Log;

const APP_NAME_MESSAGES = [
    "Getting an APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

const APK_FILE_MESSAGES = [
    //TODO
    "Getting an APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

const APPS_NAMES_FILE_MESSAGES = [
    //TODO
];

class ProcessData {

    private $process_part;
    private $process_id;
    private $ip_address;
    private $status;
    private $progress;
    private $message;

    private array $messages = [];

    public function __construct($process_id, $type, $ip_address){
        $this->process_id = $process_id;
        $this->ip_address = $ip_address;
        $this->preset();
        $this->setMessagesArray($type);

        $this->store();
    }

    private function preset(){
        $this->process_part = 0;
        $this->status = "in_queue";
        $this->progress = 0;
        $this->message = "Waiting in queue";
    }

    private function setMessagesArray($type){
        if($type == "app_name")
            $this->messages = APP_NAME_MESSAGES;
        if($type == "apk_file")
            $this->messages = APK_FILE_MESSAGES;
        if($type == "names_file") 
            $this->messages = APPS_NAMES_FILE_MESSAGES;
    }

    public function setProcessing(){
        $this->status = "processing";
        $this->store();
    }

    public function setFinished(){
        $this->status = "finished";
        $this->store();
    }

    public function nextProcessPart(){

        $this->progress = (100 / (count($this->messages) - 1)) * $this->process_part; 
        $this->message = $this->messages[$this->process_part];
        $this->store();

        $this->process_part++;
    }

    private function store(){

        $identifier = ['frontend_id' => $this->process_id];

        $data = [
            'frontend_id' => $this->process_id,
            'ip_address' => $this->ip_address,
            'status' => $this->status,
            'progress' => $this->progress,
            'message' => $this->message,
        ];

        ProcessModel::updateOrCreate($identifier, $data);
    }
}