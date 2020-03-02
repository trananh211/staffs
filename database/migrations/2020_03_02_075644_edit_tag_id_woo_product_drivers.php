<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditTagIdWooProductDrivers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_product_drivers', function (Blueprint $table) {
            $table->string('category_name',255)->after('template_id')->nullable(true);
            $table->string('tag_name',255)->after('category_name')->nullable(true);
            $table->integer('category_id')->after('tag_name')->nullable(true);
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
        Schema::table('woo_product_drivers', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropColumn('tag_name');
            $table->dropColumn('category_id');
            $table->dropColumn('woo_tag_id');
        });
    }
}
