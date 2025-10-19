<?php
/**
 * @file CustomHashTypeSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\CustomHashType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomHashTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Získaj používateľov
        $admin = User::where('email', 'admin@example.com')->first();
        $basic = User::where('email', 'basic@example.com')->first();

        if ($admin) {
            // Admin custom hash typy
            CustomHashType::create([
                'user_id' => $admin->id,
                'name' => 'advanced_ssl_fingerprint',
                'display_name' => 'Advanced SSL Fingerprint',
                'description' => 'Advanced SSL fingerprinting with certificate analysis',
                'type' => 'python_script',
                'configuration' => [
                    'algorithm' => 'sha512',
                    'include_certificates' => true,
                    'include_cipher_suites' => true,
                    'include_extensions' => true,
                    'certificate_validation' => true
                ],
                'script_path' => 'scripts/advanced_ssl_fingerprint.py',
                'is_active' => true,
                'is_public' => true,
                'usage_count' => 15,
            ]);

            CustomHashType::create([
                'user_id' => $admin->id,
                'name' => 'network_behavior_analysis',
                'display_name' => 'Network Behavior Analysis',
                'description' => 'Comprehensive network behavior analysis and fingerprinting',
                'type' => 'python_script',
                'configuration' => [
                    'time_window' => 600,
                    'packet_threshold' => 200,
                    'include_dns_queries' => true,
                    'include_http_headers' => true,
                    'include_tls_handshake' => true,
                    'behavior_patterns' => ['connection_frequency', 'data_volume', 'protocol_usage']
                ],
                'script_path' => 'scripts/network_behavior_analysis.py',
                'is_active' => true,
                'is_public' => true,
                'usage_count' => 8,
            ]);

            CustomHashType::create([
                'user_id' => $admin->id,
                'name' => 'malware_detection_hash',
                'display_name' => 'Malware Detection Hash',
                'description' => 'Specialized hash for malware detection and classification',
                'type' => 'python_script',
                'configuration' => [
                    'threat_level' => 'high',
                    'include_network_patterns' => true,
                    'include_file_analysis' => true,
                    'include_registry_changes' => true,
                    'machine_learning_model' => 'malware_classifier_v2'
                ],
                'script_path' => 'scripts/malware_detection.py',
                'is_active' => true,
                'is_public' => false,
                'usage_count' => 3,
            ]);
        }

        if ($basic) {
            // Basic user custom hash typy
            CustomHashType::create([
                'user_id' => $basic->id,
                'name' => 'simple_ssl_hash',
                'display_name' => 'Simple SSL Hash',
                'description' => 'Basic SSL fingerprinting for educational purposes',
                'type' => 'python_script',
                'configuration' => [
                    'algorithm' => 'md5',
                    'include_certificates' => false,
                    'include_cipher_suites' => true,
                    'simplified_output' => true
                ],
                'script_path' => 'scripts/simple_ssl_hash.py',
                'is_active' => true,
                'is_public' => false,
                'usage_count' => 5,
            ]);

            CustomHashType::create([
                'user_id' => $basic->id,
                'name' => 'basic_network_hash',
                'display_name' => 'Basic Network Hash',
                'description' => 'Basic network traffic analysis and hashing',
                'type' => 'python_script',
                'configuration' => [
                    'time_window' => 300,
                    'packet_threshold' => 50,
                    'include_dns_queries' => false,
                    'basic_analysis' => true
                ],
                'script_path' => 'scripts/basic_network_hash.py',
                'is_active' => true,
                'is_public' => false,
                'usage_count' => 2,
            ]);

            // Neaktívny custom hash typ
            CustomHashType::create([
                'user_id' => $basic->id,
                'name' => 'experimental_hash',
                'display_name' => 'Experimental Hash',
                'description' => 'Experimental hashing algorithm (currently disabled)',
                'type' => 'python_script',
                'configuration' => [
                    'experimental_features' => true,
                    'beta_version' => true,
                    'debug_mode' => true
                ],
                'script_path' => 'scripts/experimental_hash.py',
                'is_active' => false,
                'is_public' => false,
                'usage_count' => 0,
            ]);
        }
    }
}





