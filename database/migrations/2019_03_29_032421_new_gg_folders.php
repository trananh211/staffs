<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewGgFolders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gg_folders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',255);
            $table->string('path',255);
            $table->string('parent_path',255);
            $table->string('dir', 355);
            $table->integer('product_id');
            $table->smallInteger('level')->default(1);
            $table->tinyInteger('status')->default(0)->comment('0: chưa tạo, 1: đã tạo');
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
        Schema::dropIfExists('gg_folders');
    }
}
