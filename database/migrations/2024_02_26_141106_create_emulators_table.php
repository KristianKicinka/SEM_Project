<?php
/**
 * @file 2024_02_26_141106_create_emulators_table.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emulators', function (Blueprint $table) {
            $table->id();
            $table->string("docker_id");
            $table->string("name");
            $table->string("network_interface");
            $table->boolean("is_working")->default(0);
            $table->integer("memory");
            $table->integer("cpu_count");
            $table->string("image");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emulators');
    }
};
