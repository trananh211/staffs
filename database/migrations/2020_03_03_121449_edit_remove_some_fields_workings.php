<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditRemoveSomeFieldsWorkings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workings', function (Blueprint $table) {
            $table->dropColumn('woo_info_id');
            $table->dropColumn('woo_order_id');
            $table->dropColumn('store_order_id');
            $table->dropColumn('number');
            $table->integer('design_id')->after('id');
            $table->integer('store_id')->after('design_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workings', function (Blueprint $table) {
            $table->integer('woo_info_id')->after('id');
            $table->integer('woo_order_id')->after('woo_info_id')->comment('id chinh cua bang woo_order');
            $table->integer('store_order_id')->after('product_id')->comment('order id cua store woocommerce');
            $table->string('number', 191)->after('store_order_id');
            $table->dropColumn('design_id');
            $table->dropColumn('store_id');
        });
    }
}
