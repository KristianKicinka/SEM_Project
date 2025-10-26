<?php

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
        Schema::table('hashes', function (Blueprint $table) {
            $table->string('sni_flag')->nullable()->after('sni');
            $table->boolean('is_flagged')->default(false)->after('sni_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hashes', function (Blueprint $table) {
            $table->dropColumn(['sni_flag', 'is_flagged']);
        });
    }
};
