<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWooOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woo_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('woo_info_id');
            $table->integer('order_id');
            $table->string('number', 191);
            $table->string('email', 191);
            $table->string('order_status');
            $table->smallInteger('status')->default(0)
                ->comment('0: new, 1: working, 2: quality check, 3: production, 4: ready ');
            $table->integer('product_id');
            $table->string('product_name');
            $table->integer('quantity');
            $table->text('detail');
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
        Schema::dropIfExists('woo_orders');
    }
}
