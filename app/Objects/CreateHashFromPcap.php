<?php
/**
 * @file CreateHashFromPcap.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Objects;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use App\Exceptions\HashGeneratorFailException;

const INPUT_TYPE = "PCAP_FILE";
const PCAP_INSERTED_DIR = 'app/public/uploads/pcap_inserted/';

class CreateHashFromPcap extends CreateHash {

    protected string $pcap_file_name;
    protected array $hash_types;

    public function __construct($pcap_file_name, $hash_types) {
        $this->pcap_file_name = $pcap_file_name;
        $this->hash_types = is_array($hash_types) ? $hash_types : [];
        $this->process_id = uniqid('pcap_', true);
        $this->hashes = [];
    }

    /**
     * @brief The function ensures hash creation
     * @return array New hashes
     * @throws HashGeneratorFailException
     */
    public function create(): array {
        $pcap_file_path = storage_path(PCAP_INSERTED_DIR).$this->pcap_file_name;
        $this->hashes = $this->normalizeGeneratedHashes(
            $this->createHashes($this->pcap_file_name, $pcap_file_path)
        );

        return $this->hashes;
    }

    /**
     * @brief The function ensures hash creation and saving
     * @param array $app_data Additional application data
     * @return array Created hashes formatted for CSV export
     * @throws HashGeneratorFailException
     */
    public function createAndSave(array $app_data): array {
        $pcap_file_path = storage_path(PCAP_INSERTED_DIR).$this->pcap_file_name;
        $this->hashes = $this->normalizeGeneratedHashes(
            $this->createHashes($this->pcap_file_name, $pcap_file_path)
        );

        $db_data = [
            'app_name' => $app_data['app_name'],
            'package_name' => $app_data['package_name'],
            'version' => $app_data['app_version'] ?? '',
            'pcap_file_name' => $this->pcap_file_name,
            'pcap_file_path' => $pcap_file_path,
            'pcap_file_type' => 'PCAP',
            'is_malware' => $app_data['is_malware'],
            'is_dangerous' => $app_data['is_dangerous'],
            'hashes' => $this->hashes,
        ];

        return $this->savePcapHashes($db_data);
    }

    /**
     * @brief Normalize Python generator output to a list of hash objects
     * @param mixed $generated
     * @return array
     */
    private function normalizeGeneratedHashes($generated): array {
        if (is_object($generated) && isset($generated->hashes)) {
            $generated = $generated->hashes;
        }

        return is_array($generated) ? array_values($generated) : [];
    }

    /**
     * @brief Extract custom_* fields from a generated hash object
     * @param object|array $hash
     * @return array
     */
    private function extractCustomHashes($hash): array {
        $custom_hashes = [];
        foreach ($hash as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'custom_')) {
                $custom_hashes[$key] = $value;
            }
        }

        return $custom_hashes;
    }

    /**
     * @brief The function ensures saving hashes to database
     * @param array $data Data to save
     * @return array Saved hashes formatted for CSV export
     */
    private function savePcapHashes(array $data): array {
        $identifier = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        $new_application = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
            'is_malware' => (bool) $data['is_malware'],
            'is_dangerous' => (bool) $data['is_dangerous'],
        ];

        $application = Application::firstOrCreate($identifier, $new_application);
        $application->is_malware = (bool) $data['is_malware'];
        $application->is_dangerous = (bool) $data['is_dangerous'];
        $application->save();

        File::create([
            'name' => $data['pcap_file_name'],
            'type' => $data['pcap_file_type'],
            'path' => $data['pcap_file_path'],
            'app_id' => $application->id,
        ]);

        $saved_hashes = [];
        foreach ($data['hashes'] as $hash) {
            $custom_hashes = $this->extractCustomHashes($hash);

            $db_hash = Hash::create([
                'app_id' => $application->id,
                'ja3_hash' => $hash->ja3_hash ?? null,
                'ja3s_hash' => $hash->ja3s_hash ?? null,
                'ja4_hash' => $hash->ja4_hash ?? null,
                'ja4s_hash' => $hash->ja4s_hash ?? null,
                'ja4x_hash' => $hash->ja4x_hash ?? null,
                'custom_hashes' => !empty($custom_hashes) ? $custom_hashes : null,
                'sni' => $hash->sni ?? null,
                'sni_flag' => $hash->sni_flag ?? null,
                'is_flagged' => (bool) ($hash->is_flagged ?? false),
                'ip_src' => $hash->ip_src ?? null,
                'port_src' => $hash->port_src ?? null,
                'ip_dest' => $hash->ip_dest ?? null,
                'port_dest' => $hash->port_dest ?? null,
            ]);

            $saved_hashes[] = [
                'id' => $db_hash->id,
                'created_at' => optional($db_hash->created_at)->toDateTimeString(),
                'app_name' => $application->name,
                'package_name' => $application->package_name,
                'app_version' => $application->version,
                'sni' => $db_hash->sni,
                'sni_flag' => $db_hash->sni_flag,
                'is_flagged' => $db_hash->is_flagged,
                'ja3_hash' => $db_hash->ja3_hash,
                'ja3s_hash' => $db_hash->ja3s_hash,
                'ja4_hash' => $db_hash->ja4_hash,
                'ja4s_hash' => $db_hash->ja4s_hash,
                'ja4x_hash' => $db_hash->ja4x_hash,
                'custom_hashes' => $db_hash->custom_hashes,
                'ip_src' => $db_hash->ip_src,
                'port_src' => $db_hash->port_src,
                'ip_dest' => $db_hash->ip_dest,
                'port_dest' => $db_hash->port_dest,
            ];
        }

        return $saved_hashes;
    }
}
