<?php
/**
 * @file HashSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Hash;
use App\Models\Application;
use App\Models\Process;
use App\Models\CustomHashType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HashSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Získaj prvé aplikácie a procesy
        $whatsapp = Application::where('package_name', 'com.whatsapp')->first();
        $facebook = Application::where('package_name', 'com.facebook.katana')->first();
        $suspicious = Application::where('package_name', 'com.suspicious.app')->first();

        // Vytvor testovacie procesy
        $process1 = Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.100',
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Hash generation completed successfully',
        ]);

        $process2 = Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.101',
            'status' => 'running',
            'progress' => 75,
            'message' => 'Processing network traffic...',
        ]);

        $process3 = Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.102',
            'status' => 'failed',
            'progress' => 30,
            'message' => 'Failed to generate hash - insufficient data',
        ]);

        // Vytvor custom hash typy
        $customHashType1 = CustomHashType::create([
            'user_id' => 1, // Admin user
            'name' => 'custom_ssl_fingerprint',
            'display_name' => 'Custom SSL Fingerprint',
            'description' => 'Custom SSL fingerprinting algorithm',
            'type' => 'python_script',
            'configuration' => [
                'algorithm' => 'sha256',
                'include_certificates' => true,
                'include_cipher_suites' => true
            ],
            'script_path' => 'scripts/custom_hash_generators.py',
            'is_active' => true,
            'is_public' => true,
            'usage_count' => 0,
        ]);

        $customHashType2 = CustomHashType::create([
            'user_id' => 2, // Basic user
            'name' => 'network_behavior_hash',
            'display_name' => 'Network Behavior Hash',
            'description' => 'Hash based on network behavior patterns',
            'type' => 'python_script',
            'configuration' => [
                'time_window' => 300,
                'packet_threshold' => 100,
                'include_dns_queries' => true
            ],
            'script_path' => 'scripts/behavior_hash.py',
            'is_active' => true,
            'is_public' => false,
            'usage_count' => 0,
        ]);

        // Vytvor testovacie hashe
        if ($whatsapp) {
            Hash::create([
                'app_id' => $whatsapp->id,
                'process_id' => $process1->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'whatsapp.com',
                'sni_flag' => null,
                'is_flagged' => false,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'ssl_fingerprint' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                    'certificate_hash' => 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7'
                ],
                'custom_hash_type_id' => $customHashType1->id,
                'ip_src' => '192.168.1.100',
                'port_src' => 44300,
                'ip_dest' => '157.240.1.1',
                'port_dest' => 443,
            ]);
        }

        if ($facebook) {
            Hash::create([
                'app_id' => $facebook->id,
                'process_id' => $process2->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'facebook.com',
                'sni_flag' => null,
                'is_flagged' => false,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'behavior_pattern' => 'c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8',
                    'network_signature' => 'd4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9'
                ],
                'custom_hash_type_id' => $customHashType2->id,
                'ip_src' => '192.168.1.101',
                'port_src' => 44301,
                'ip_dest' => '31.13.69.35',
                'port_dest' => 443,
            ]);
        }

        if ($suspicious) {
            Hash::create([
                'app_id' => $suspicious->id,
                'process_id' => $process3->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'suspicious-domain.com',
                'sni_flag' => 'blacklisted_communication',
                'is_flagged' => true,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'malware_signature' => 'e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0',
                    'threat_indicator' => 'f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1'
                ],
                'custom_hash_type_id' => $customHashType1->id,
                'ip_src' => '192.168.1.102',
                'port_src' => 44302,
                'ip_dest' => '10.0.0.1',
                'port_dest' => 443,
            ]);
        }

        // Pridaj ďalšie hashe s rôznymi flagmi
        if ($whatsapp) {
            // Advertisement hash
            Hash::create([
                'app_id' => $whatsapp->id,
                'process_id' => $process1->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'doubleclick.net',
                'sni_flag' => 'advertisement_communication',
                'is_flagged' => true,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'ad_network_signature' => 'g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2'
                ],
                'custom_hash_type_id' => $customHashType1->id,
                'ip_src' => '192.168.1.100',
                'port_src' => 44303,
                'ip_dest' => '172.217.16.1',
                'port_dest' => 443,
            ]);

            // Analytics hash
            Hash::create([
                'app_id' => $whatsapp->id,
                'process_id' => $process1->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'analytics.google.com',
                'sni_flag' => 'analytics_communication',
                'is_flagged' => true,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'analytics_tracking' => 'h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3'
                ],
                'custom_hash_type_id' => $customHashType2->id,
                'ip_src' => '192.168.1.100',
                'port_src' => 44304,
                'ip_dest' => '142.250.191.1',
                'port_dest' => 443,
            ]);

            // CDN hash
            Hash::create([
                'app_id' => $whatsapp->id,
                'process_id' => $process1->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'cloudfront.net',
                'sni_flag' => 'cdn_communication',
                'is_flagged' => true,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'cdn_optimization' => 'i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4'
                ],
                'custom_hash_type_id' => $customHashType1->id,
                'ip_src' => '192.168.1.100',
                'port_src' => 44305,
                'ip_dest' => '13.32.0.1',
                'port_dest' => 443,
            ]);

            // Shared API hash
            Hash::create([
                'app_id' => $whatsapp->id,
                'process_id' => $process1->id,
                'ja3_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'ja3s_hash' => '769,47-53-5-10-49161-49162-49171-49172-50-56-19-4,0-10-11-13-5,23-24-25,0',
                'hash_type' => 'ja3',
                'sni' => 'googleapis.com',
                'sni_flag' => 'shared_api_communication',
                'is_flagged' => true,
                'ja4_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4s_hash' => 't13d1519h2_8daaf6152771_af3e0c77f',
                'ja4x_hash' => ['tls_version' => 'TLS 1.3', 'cipher_suites' => ['TLS_AES_256_GCM_SHA384']],
                'custom_hashes' => [
                    'api_authentication' => 'j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5'
                ],
                'custom_hash_type_id' => $customHashType2->id,
                'ip_src' => '192.168.1.100',
                'port_src' => 44306,
                'ip_dest' => '142.250.191.1',
                'port_dest' => 443,
            ]);
        }
    }
}





