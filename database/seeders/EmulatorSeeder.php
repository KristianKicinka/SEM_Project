<?php
/**
 * @file EmulatorSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Emulator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmulatorSeeder extends Seeder {

    /**
     * Run the database seeds.
     */
    public function run(): void {

        // Inserting emulator 01
        Emulator::create([
            'name' => 'emulator_01',
            'network_interface' => 'br-a40d220ffe11',
            'is_working' => false,
        ]);

        // Inserting emulator 02
        Emulator::create([
            'name' => 'emulator_02',
            'network_interface' => 'br-2da9e5e6c91e',
            'is_working' => false,
        ]);
    }
}
