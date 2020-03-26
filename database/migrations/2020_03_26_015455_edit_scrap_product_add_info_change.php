<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditScrapProductAddInfoChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scrap_products', function (Blueprint $table) {
            $table->smallInteger('status_tool')->after('status')->default(0)->comment('1: changing');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scrap_products', function (Blueprint $table) {
            $table->dropColumn('status_tool');
        });
    }
}
