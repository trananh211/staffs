<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooTemplateAddInfoChangeTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_templates', function (Blueprint $table) {
            $table->text('product_code')->after('template_id')->nullable(true);
            $table->text('product_name_exclude')->after('product_code')->nullable(true);
            $table->text('product_name_change')->after('product_code')->nullable(true);
            $table->float('origin_price')->after('product_name_exclude')->default(0);
            $table->float('sale_price')->after('origin_price')->default(0);
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
            $table->dropColumn('product_code');
            $table->dropColumn('product_name_exclude');
            $table->dropColumn('product_name_change');
            $table->dropColumn('origin_price');
            $table->dropColumn('sale_price');
        });
    }
}
