<?php

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
        Schema::create('hashes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('app_id')->unsigned();
            $table->bigInteger('process_id')->unsigned()->nullable();

            $table->string('ip_src')->nullable();
            $table->string('port_src')->nullable();
            $table->string('ip_dest')->nullable();
            $table->string('port_dest')->nullable();

            $table->string('sni')->nullable();
            $table->string('ja3_hash')->nullable();
            $table->string('ja3s_hash')->nullable();
            $table->string('ja4_hash')->nullable();
            $table->string('ja4s_hash')->nullable();
            $table->json('ja4x_hash')->nullable();
            $table->timestamps();
            $table->foreign('app_id')->references('id')->on('applications');
            $table->foreign('process_id')->references('id')->on('processes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hashes');
    }
};
