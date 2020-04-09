<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditDesignsProductCodeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->integer('product_code_id')->after('tool_category_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn('product_code_id');
        });
    }
}
