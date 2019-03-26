<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWooOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->string('payment_method',100)->after('detail')->nullable(true);
            $table->text('customer_note')->after('detail')->nullable(true);
            $table->string('transaction_id',60)->after('detail')->nullable(true);
            $table->float('price', 15)->after('quantity')->default(0);
            $table->integer('variation_id')->after('price')->default(0);
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
            $table->dropColumn('payment_method');
            $table->dropColumn('customer_note');
            $table->dropColumn('transaction_id');
            $table->dropColumn('price');
            $table->dropColumn('variation_id');
        });
    }
}
