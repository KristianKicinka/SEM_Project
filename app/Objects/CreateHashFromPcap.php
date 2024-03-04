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

    public function __construct($pcap_file_name, $hash_types) {
        $this->pcap_file_name = $pcap_file_name;
        $this->hash_types = $hash_types;
    }

    public function create(){
        $pcap_file_path = storage_path(PCAP_INSERTED_DIR).$this->pcap_file_name;

        $this->hashes = $this->createHashes($this->hash_types, $this->pcap_file_name, $pcap_file_path);

        return $this->hashes;
    }


    public function createAndSave($app_data){
        $pcap_file_path = storage_path(PCAP_INSERTED_DIR).$this->pcap_file_name;
        
        $this->hashes = $this->createHashes($this->hash_types, $this->pcap_file_name, $pcap_file_path);

        $db_data = [
            'app_name' => $app_data['app_name'],
            'package_name' => $app_data['package_name'],
            'version' => $app_data['app_version'],
            'pcap_file_name' => $this->pcap_file_name,
            'pcap_file_path' => $pcap_file_path,
            'pcap_file_type' => 'PCAP',
            'is_malware' => $app_data['is_malware'],
            'is_dangerous' => $app_data['is_dangerous'],
            'hashes' => $this->hashes,
        ];

        $this->save_hashes($db_data);

        return $this->hashes;
    }

    private function save_hashes($data){

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

        foreach($data["hashes"] as $hash){
           

            /*
            $identifier = [
                'app_id' => $application->id,
                'hash' => $hash,
                'hash_type' => $hash_type,
            ];
            */

            $new_record = [
                'app_id' => $application->id,
                'ja3_hash' => $hash->ja3_hash,
                'ja3s_hash' => $hash->ja3s_hash,
                'ja4_hash' => $hash->ja4_hash,
                'ja4s_hash' => $hash->ja4s_hash,
                'hash_type' => null,
                'sni' => $hash->sni,
                'is_malware' => $data['is_malware'],
                'is_dangerous' => $data['is_dangerous'],
            ];
    
            $db_hash = Hash::create($new_record);
            $db_hash->save();

            //Hash::firstOrCreate($identifier, $new_record);
        }
    }

}