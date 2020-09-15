<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAddAutoSkuIdToWooTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_templates', function (Blueprint $table) {
            $table->integer('sku_auto_id')->after('website_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_templates', function (Blueprint $table) {
            $table->dropColumn('sku_auto_id');
        });
    }
}
