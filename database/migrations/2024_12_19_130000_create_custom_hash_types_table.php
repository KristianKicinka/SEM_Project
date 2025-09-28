<?php
/**
 * @file 2024_12_19_130000_create_custom_hash_types_table.php
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
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_hash_types', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->enum('type', ['simple_tls', 'custom_algorithm', 'python_script']);
            $table->json('configuration'); // Konfigurácia generátora
            $table->string('script_path')->nullable(); // Cesta k Python skriptu ak je type=python_script
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // Môže používať iný používateľ
            $table->integer('usage_count')->default(0); // Počet použití
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_active']);
            $table->index(['is_public', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_hash_types');
    }
};

