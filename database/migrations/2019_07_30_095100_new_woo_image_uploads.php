<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWooImageUploads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woo_image_uploads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('path');
            $table->text('url');
            $table->integer('woo_product_driver_id');
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
        Schema::dropIfExists('woo_image_uploads');
    }
}
