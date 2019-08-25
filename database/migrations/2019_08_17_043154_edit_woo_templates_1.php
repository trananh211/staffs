<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooTemplates1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_templates', function (Blueprint $table) {
            $table->text('product_name')->after('id');
            $table->integer('supplier_id')->after('template_id')->nullable(true);
            $table->float('base_price')->after('template_id')->nullable(true);
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
            $table->dropColumn('product_name');
            $table->dropColumn('supplier_id');
            $table->dropColumn('base_price');
        });
    }
}
