<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooOrdersAddExcelFulfillId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_orders', function (Blueprint $table) {
            $table->integer('excel_fulfill_id')->after('status')->nullable(true);
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
            $table->dropColumn('excel_fulfill_id');
        });
    }
}
