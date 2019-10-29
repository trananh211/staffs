<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooTemplates3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_templates', function (Blueprint $table) {
            $table->tinyInteger('status')->after('store_id')->default(0);
            $table->integer('website_id')->after('store_id')->nullable(true);
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
            $table->dropColumn('status');
            $table->dropColumn('website_id');
        });
    }
}
