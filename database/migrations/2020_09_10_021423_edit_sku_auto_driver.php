<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditSkuAutoDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_product_drivers', function (Blueprint $table) {
            $table->string('sku_auto_string',20)->after('woo_product_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_product_drivers', function (Blueprint $table) {
            $table->dropColumn('sku_auto_string');
        });
    }
}
