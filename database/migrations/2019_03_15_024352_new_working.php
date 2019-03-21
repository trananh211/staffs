<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWorking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('woo_info_id');
            $table->integer('woo_order_id')->comment('id chinh cua bang woo_order');
            $table->integer('product_id');
            $table->integer('store_order_id')->comment('order id cua store woocommerce');
            $table->integer('worker_id');
            $table->integer('qc_id')->nullable();
            $table->smallInteger('status')->default(0);
            $table->smallInteger('redo')->default(0);
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
        Schema::dropIfExists('workings');
    }
}
