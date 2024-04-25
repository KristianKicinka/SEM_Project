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
            'network_interface' => 'br-em_01',
            'is_working' => false,
        ]);

        // Inserting emulator 02
        Emulator::create([
            'name' => 'emulator_02',
            'network_interface' => 'br-em_02',
            'is_working' => false,
        ]);
    }
}
