<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditScrapProducts1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scrap_products', function (Blueprint $table) {
            $table->text('tag_name')->after('category_name')->nullable(true);
            $table->integer('woo_tag_id')->after('woo_category_id')->nullable(true);
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
            $table->dropColumn('tag_name');
            $table->dropColumn('woo_tag_id');
        });
    }
}
