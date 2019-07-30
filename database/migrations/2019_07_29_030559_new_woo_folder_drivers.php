<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWooFolderDrivers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woo_folder_drivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->text('path');
            $table->integer('template_id');
            $table->integer('store_id');
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
        Schema::dropIfExists('woo_folder_drivers');
    }
}
