<?php
/**
 * @file 2024_12_19_131000_add_custom_hash_type_id_to_hashes_table.php
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
            $table->bigInteger('custom_hash_type_id')->unsigned()->nullable()->after('custom_hashes');
            $table->foreign('custom_hash_type_id')->references('id')->on('custom_hash_types')->onDelete('set null');
            $table->index('custom_hash_type_id');
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
            $table->dropForeign(['custom_hash_type_id']);
            $table->dropIndex(['custom_hash_type_id']);
            $table->dropColumn('custom_hash_type_id');
        });
    }
};

