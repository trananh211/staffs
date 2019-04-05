<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewGgFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gg_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',255);
            $table->text('path');
            $table->string('parent_path',355);
            $table->integer('product_id')->nullable(true);
            $table->integer('idea_id')->nullable(true);
            $table->integer('idea_file_id')->nullable(true);
            $table->integer('working_file_id')->nullable(true);
            $table->smallInteger('status')->default(0);
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
        Schema::dropIfExists('gg_files');
    }
}
