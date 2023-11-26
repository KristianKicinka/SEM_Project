<?php

namespace App\Objects;

use App\Objects\CreateHash;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;

const INPUT_TYPE = "PCAP_FILE";
const PCAP_INSERTED_DIR = 'app/public/uploads/pcap_inserted/';

class CreateHashFromPcap extends CreateHash {

    protected string $pcap_file_name;
    protected array $hash_types;
    protected array $app_data;

    public function __construct($pcap_file_name, $hash_types, $app_data) {
        $this->pcap_file_name = $pcap_file_name;
        $this->hash_types = $hash_types;
        $this->app_data = $app_data;
    }


    public function create(){
        $pcap_file_path = storage_path(PCAP_INSERTED_DIR).$this->pcap_file_name;
        
        $this->hashes = $this->createHashes($this->hash_types, $this->pcap_file_name, $pcap_file_path);

        $db_data = [
            'app_name' => $this->app_data['app_name'],
            'package_name' => $this->app_data['package_name'],
            'version' => $this->app_data['app_version'],
            'pcap_file_name' => $this->pcap_file_name,
            'pcap_file_path' => $pcap_file_path,
            'pcap_file_type' => 'PCAP',
            'is_malware' => $this->app_data['is_malware'],
            'hashes' => $this->hashes,
        ];

        $this->save_hashes($db_data);

        return $this->hashes;
    }

    private function save_hashes ($data) {

        $identifier = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];
    
        $new_application = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        $application = Application::firstOrCreate($identifier, $new_application);

        $db_file = File::create([
            'name' => $data['pcap_file_name'],
            'type' => $data['pcap_file_type'],
            'path' => $data['pcap_file_path'],
            'app_id' => $application->id,
        ]);

        $db_file->save();

        foreach($data['hashes'] as $hash_type => $hashes){
            foreach($hashes as $hash){

                $identifier = [
                    'app_id' => $application->id,
                    'hash' => $hash,
                    'hash_type' => $hash_type,
                ];

                $new_record = [
                    'app_id' => $application->id,
                    'hash' => $hash,
                    'hash_type' => $hash_type,
                    'is_malware' => $data['is_malware'],
                ];

                Hash::firstOrCreate($identifier, $new_record);
            }
        }
    }

}