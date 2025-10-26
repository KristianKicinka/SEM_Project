<?php
/**
 * @file 2024_12_19_120000_add_custom_hashes_to_hashes_table.php
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
        Schema::table('hashes', function (Blueprint $table) {
            // Pridanie JSON stĺpca pre vlastné hashe
            $table->json('custom_hashes')->nullable()->after('ja4x_hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hashes', function (Blueprint $table) {
            $table->dropColumn('custom_hashes');
        });
    }
};

