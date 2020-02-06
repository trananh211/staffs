<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewFeedProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feed_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('woo_product_name');
            $table->text('woo_slug');
            $table->text('woo_image')->nullable(true);
            $table->integer('category_id');
            $table->integer('woo_product_id');
            $table->integer('store_id');
            $table->string('category_name',255)->nullable(true);
            $table->string('tag_name',255)->nullable(true);
            $table->integer('scrap_product_id');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('check')->default(0);
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
        Schema::dropIfExists('feed_products');
    }
}
