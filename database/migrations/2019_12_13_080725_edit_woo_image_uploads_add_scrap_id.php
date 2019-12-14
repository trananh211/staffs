<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooImageUploadsAddScrapId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_image_uploads', function (Blueprint $table) {
            $table->integer('woo_scrap_product_id')->after('woo_product_driver_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_image_uploads', function (Blueprint $table) {
            $table->dropColumn('woo_scrap_product_id');
        });
    }
}
