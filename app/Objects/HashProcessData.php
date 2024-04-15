<?php

namespace App\Objects;

use App\Models\Process as ProcessModel;
use App\Events\ProcessUpdate;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;

// Status messages for processing creation hashes from app name
const APP_NAME_MESSAGES = [
    "Getting an application package name",
    "Downloading APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

// Status messages for processing creation hashes from APK file
const APK_FILE_MESSAGES = [
    "Getting an APK file",
    "Installing an application in a virtual environment",
    "Network communication analysis",
    "Creating application fingerprints",
    "Uploading fingerprints to the database system",
];

// Status messages for processing creation hashes from app names file
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
    private string $process_name;
    private ?string $channel_id;
    private ?int $api_id;
    private string $ip_address;
    private string $status;
    private int $progress;
    private string $message;
    private array $messages = [];

    /**
     * @param string $process_id Hash process ID
     * @param string $type Hash process type
     * @param string $ip_address Client IP address
     * @param string|null $channel_id Pusher channel ID
     * @param string $process_name Name of currently processing hash process
     */
    public function __construct(
        string $process_id, string $type, string $ip_address, ?string $channel_id, string $process_name, ?int $api_id){

        $this->process_id = $process_id;
        $this->process_name = $process_name;
        $this->channel_id = $channel_id;
        $this->ip_address = $ip_address;
        $this->api_id = $api_id;
        $this->preset();
        $this->setMessagesArray($type);

        $this->store();
    }

    /**
     * @brief The function ensures the presetting of class values
     * @return void
     */
    private function preset(): void {
        $this->process_part = 0;
        $this->status = "in_queue";
        $this->progress = 0;
        $this->message = "Waiting in queue";
    }

    /**
     * @brief The function ensures the setting of status messages array values
     * @param string $type Process creation type
     * @return void
     */
    private function setMessagesArray(string $type): void {
        if($type == "APP_NAME")
            $this->messages = APP_NAME_MESSAGES;
        if($type == "APK_FILE")
            $this->messages = APK_FILE_MESSAGES;
        if($type == "NAMES_FILE")
            $this->messages = APPS_NAMES_FILE_MESSAGES;
    }

    /**
     * @brief The function ensures the setting of processing state value
     * @return void
     */
    public function setProcessing(): void {
        $this->status = "processing";
        $this->store();
    }

    /**
     * @brief The function ensures the setting of finished state value
     * @return void
     */
    public function setFinished(): void {
        $this->status = "finished";
        $this->store();
    }

     /**
     * @brief The function ensures the setting of failed state value
     * @return void
     */
    public function setFailed(): void {
        $this->status = "failed";
        $this->store();
    }

    /**
     * @brief The function ensures the setting of next part of process
     * @return void
     */
    public function nextProcessPart(): void {

        $this->progress = (100 / (count($this->messages) - 1)) * $this->process_part;
        $this->message = $this->messages[$this->process_part];
        $this->store();

        $this->process_part++;
    }

    /**
     * @brief The function serves to get current process part
     * @return int Process part ID
     */
    public function getProcessPart(): int {
        return $this->process_part;
    }

    /**
     * @brief The function ensures saving data to database and send process notification to pusher channel
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

        // Save process to DB
        ProcessModel::updateOrCreate($identifier, $data);

        // Send process to pusher channel
        if($this->channel_id)
            ProcessUpdate::dispatch(
                $this->channel_id, $this->process_id, $this->status,
                $this->progress, $this->message, $this->process_name
            );

        // Update api status if task was created from api
        if($this->api_id){
            DB::table('api_requests')
                ->where('id', '=', $this->api_id)->update(['status' => $this->status]);
        }
    }
}
