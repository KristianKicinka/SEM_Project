<?php
/**
 * @file DatabaseSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Najprv vytvoríme používateľov
            UserSeeder::class,
            
            // Potom emulátory
            EmulatorSeeder::class,
            
            // Aplikácie
            ApplicationSeeder::class,
            
            // Custom hash typy (potrebujú používateľov)
            CustomHashTypeSeeder::class,
            
            // Procesy
            ProcessSeeder::class,
            
            // Hashe (potrebujú aplikácie, procesy a custom hash typy)
            HashSeeder::class,
        ]);
    }
}





