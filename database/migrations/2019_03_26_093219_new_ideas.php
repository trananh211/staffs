<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewIdeas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',255);
            $table->string('title',255);
            $table->string('path',300);
            $table->text('require')->nullable(true);
            $table->integer('worker_id');
            $table->integer('qc_id')->nullable();
            $table->smallInteger('status')->default(0);
            $table->smallInteger('redo')->default(0);
            $table->text('reason')->nullable(true);
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
        Schema::dropIfExists('ideas');
    }
}
