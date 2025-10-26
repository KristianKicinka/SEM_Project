<?php
/**
 * @file ApplicationSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Testovacie aplikácie
        Application::create([
            'name' => 'WhatsApp',
            'package_name' => 'com.whatsapp',
            'version' => '2.23.24.78',
            'is_malware' => false,
            'is_dangerous' => false,
        ]);

        Application::create([
            'name' => 'Facebook',
            'package_name' => 'com.facebook.katana',
            'version' => '430.0.0.50.108',
            'is_malware' => false,
            'is_dangerous' => false,
        ]);

        Application::create([
            'name' => 'Instagram',
            'package_name' => 'com.instagram.android',
            'version' => '302.0.0.37.120',
            'is_malware' => false,
            'is_dangerous' => false,
        ]);

        Application::create([
            'name' => 'Suspicious App',
            'package_name' => 'com.suspicious.app',
            'version' => '1.0.0',
            'is_malware' => true,
            'is_dangerous' => true,
        ]);

        Application::create([
            'name' => 'TikTok',
            'package_name' => 'com.zhiliaoapp.musically',
            'version' => '32.0.4',
            'is_malware' => false,
            'is_dangerous' => false,
        ]);

        Application::create([
            'name' => 'Banking App',
            'package_name' => 'com.bank.app',
            'version' => '2.1.5',
            'is_malware' => false,
            'is_dangerous' => false,
        ]);
    }
}



