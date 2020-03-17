<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewFulfills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fulfills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('number',255);
            $table->string('sku',155);
            $table->string('product_name',255);
            $table->integer('woo_order_id');
            $table->integer('design_id');
            $table->integer('product_id');
            $table->integer('working_id');
            $table->integer('store_id');
            $table->tinyInteger('status')->default(0);
            $table->string('size', 100);
            $table->string('variation_detail', 255);
            $table->text('product_image');
            $table->integer('quantity');
            $table->string('color',255);
            $table->string('currency',100);
            $table->float('base_price')->default(0);
            $table->float('item_price')->default(0);
            $table->float('shipping_cost')->default(0);
            $table->string('fullname',255);
            $table->string('first_name',255);
            $table->string('last_name',255);
            $table->text('address');
            $table->string('city',155);
            $table->string('state',155);
            $table->string('country',155);
            $table->string('postcode',10);
            $table->string('phone',15);
            $table->string('shipping',255);
            $table->text('customer_note');
            $table->string('email',255);
            $table->string('exact_art_work',255)->nullable(true);
            $table->string('back_inscription',255)->nullable(true);
            $table->string('memo',255)->nullable(true);
            $table->string('design_position',255)->nullable(true);
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
        Schema::dropIfExists('fulfills');
    }
}
