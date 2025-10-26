<?php
/**
 * @file ProcessSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Process;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Testovacie procesy s rôznymi stavmi
        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.10',
            'status' => 'pending',
            'progress' => 0,
            'message' => 'Waiting to start hash generation...',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.11',
            'status' => 'running',
            'progress' => 25,
            'message' => 'Analyzing network traffic...',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.12',
            'status' => 'running',
            'progress' => 50,
            'message' => 'Generating JA3/JA4 hashes...',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.13',
            'status' => 'running',
            'progress' => 75,
            'message' => 'Applying custom hash algorithms...',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.14',
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Hash generation completed successfully',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.15',
            'status' => 'failed',
            'progress' => 30,
            'message' => 'Failed to process pcap file - corrupted data',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.16',
            'status' => 'failed',
            'progress' => 60,
            'message' => 'Insufficient network traffic for hash generation',
        ]);

        Process::create([
            'job_id' => 'job_' . uniqid(),
            'ip_address' => '192.168.1.17',
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Custom hash generation completed with warnings',
        ]);
    }
}





