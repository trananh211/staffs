<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAddMoreStatusTool extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_product_drivers', function (Blueprint $table) {
            $table->smallInteger('status_tool')->after('status')->default(0);
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
            $table->dropColumn('status_tool');
        });
    }
}
