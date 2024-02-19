<?php

namespace App\Objects;

use App\Models\Process as ProcessModel;
use Illuminate\Support\Facades\Log;

const APP_NAME_MESSAGES = [
    "Getting an application package name",
    "Downloading APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

const APK_FILE_MESSAGES = [
    "Getting an APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

const APPS_NAMES_FILE_MESSAGES = [
    "Loading app parameters from file",
    "Downloading APK files",
    "Installing applications",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

class HashProcessData {

    private int $process_part;
    private string $process_id;
    private string $ip_address;
    private string $status;
    private int $progress;
    private string $message;

    private array $messages = [];

    public function __construct($process_id, $type, $ip_address){
        $this->process_id = $process_id;
        $this->ip_address = $ip_address;
        $this->preset();
        $this->setMessagesArray($type);

        $this->store();
    }

    /**
     * @return void
     */
    private function preset(): void {
        $this->process_part = 0;
        $this->status = "in_queue";
        $this->progress = 0;
        $this->message = "Waiting in queue";
    }

    /**
     * @param $type
     * @return void
     */
    private function setMessagesArray($type): void {
        if($type == "APP_NAME")
            $this->messages = APP_NAME_MESSAGES;
        if($type == "APK_FILE")
            $this->messages = APK_FILE_MESSAGES;
        if($type == "NAMES_FILE")
            $this->messages = APPS_NAMES_FILE_MESSAGES;
    }

    /**
     * @return void
     */
    public function setProcessing(): void {
        $this->status = "processing";
        $this->store();
    }

    /**
     * @return void
     */
    public function setFinished(): void {
        $this->status = "finished";
        $this->store();
    }

     /**
     * @return void
     */
    public function setFailed(): void {
        $this->status = "failed";
        $this->store();
    }

    /**
     * @return void
     */
    public function nextProcessPart(): void {

        $this->progress = (100 / (count($this->messages) - 1)) * $this->process_part;
        $this->message = $this->messages[$this->process_part];
        $this->store();

        $this->process_part++;
    }

    /**
     * @return void
     */
    private function store(): void {

        $identifier = ['job_id' => $this->process_id];

        $data = [
            'job_id' => $this->process_id,
            'ip_address' => $this->ip_address,
            'status' => $this->status,
            'progress' => $this->progress,
            'message' => $this->message,
        ];

        ProcessModel::updateOrCreate($identifier, $data);
    }
}
