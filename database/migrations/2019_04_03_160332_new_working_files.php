<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWorkingFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('working_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',255);
            $table->string('path',355);
            $table->integer('worker_id');
            $table->integer('working_id');
            $table->smallInteger('is_mockup')->default(0);
            $table->smallInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('working_files');
    }
}
