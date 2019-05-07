<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditGgFiles1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gg_files', function (Blueprint $table) {
            $table->tinyInteger('type')->default(1)->after('parent_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gg_files', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
