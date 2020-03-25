<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewFileFulfills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_fulfills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number',255);
            $table->integer('woo_order_id');
            $table->integer('working_file_id');
            $table->text('path');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('file_fulfills');
    }
}
