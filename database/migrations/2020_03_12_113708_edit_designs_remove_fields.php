<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditDesignsRemoveFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn('staff_id');
            $table->dropColumn('qc_id');
            $table->integer('category_id')->after('variation')->default(0);
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
            $table->integer('staff_id')->after('variation')->default(0);
            $table->integer('qc_id')->after('staff_id')->default(0);
            $table->dropColumn('category_id');
        });
    }
}
