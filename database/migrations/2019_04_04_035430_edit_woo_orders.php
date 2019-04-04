<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->text('fullname')->after('email')->nullable(true);
            $table->text('address')->after('fullname')->nullable(true);
            $table->text('city')->after('address')->nullable(true);
            $table->text('state')->after('city')->nullable(true);
            $table->text('country')->after('city')->nullable(true);
            $table->text('postcode')->after('city')->nullable(true);
            $table->text('phone')->after('city')->nullable(true);
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
            $table->dropColumn('fullname');
            $table->dropColumn('address');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('country');
            $table->dropColumn('postcode');
            $table->dropColumn('phone');
        });
    }
}
