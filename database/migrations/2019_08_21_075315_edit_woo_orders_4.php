<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooOrders4 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->string('variation_detail',255)->after('variation_id')->nullable(true);
            $table->string('variation_full_detail',255)->after('variation_detail')->nullable(true);
            $table->smallInteger('custom_status')->after('variation_full_detail')->default(0);
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
            $table->dropColumn('variation_detail');
            $table->dropColumn('variation_full_detail');
            $table->dropColumn('custom_status');
        });
    }
}
