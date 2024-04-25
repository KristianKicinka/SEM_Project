<?php
/**
 * @file UserSeeder.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {

    /**
     * Run the database seeds.
     */
    public function run(): void {

        // Inserting Admin user
        User::create([
            'name' => 'Admin',
            'surname' => 'User',
            'email' => 'admin@example.com',
            'phone' => '+421911369367',
            'password' => bcrypt('AdminPass123'),
            'role' => 'admin',
        ]);

        // Inserting Basic user
        User::create([
            'name' => 'Basic',
            'surname' => 'User',
            'email' => 'user@example.com',
            'phone' => '+421911369369',
            'password' => bcrypt('UserPass123'),
            'role' => 'basic_user',
        ]);
    }
}
