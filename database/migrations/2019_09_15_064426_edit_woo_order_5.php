<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooOrder5 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->Integer('paypal_id')->after('payment_method')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->dropColumn('paypal_id');
        });
    }
}
