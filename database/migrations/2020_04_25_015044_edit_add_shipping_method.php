<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAddShippingMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->dropColumn('woo_order_id');
            $table->string('shipping_method',100)->after('is_check');
            $table->smallInteger('payment_up_tracking')->after('payment_status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->integer('woo_order_id')->after('tracking_number');
            $table->dropColumn('shipping_method');
            $table->dropColumn('payment_up_tracking');
        });
    }
}
