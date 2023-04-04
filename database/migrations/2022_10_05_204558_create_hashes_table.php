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
            $table->string('hash');
            $table->string('hash_type');
            $table->timestamps();
            $table->foreign('app_id')->references('id')->on('applications'); 
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
