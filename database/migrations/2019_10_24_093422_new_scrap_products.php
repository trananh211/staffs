<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewScrapProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrap_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('category_name',255);
            $table->text('link');
            $table->integer('website_id');
            $table->string('website',300);
            $table->integer('template_id');
            $table->integer('woo_category_id')->nullable(true);
            $table->integer('store_id');
            $table->smallInteger('status')->default(0);
            $table->integer('woo_product_id')->nullable(true);
            $table->text('woo_product_name')->nullable(true);
            $table->text('woo_slug')->nullable(true);
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
        Schema::dropIfExists('scrap_products');
    }
}
