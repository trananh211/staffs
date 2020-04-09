<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAddPathGoogleDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tool_categories', function (Blueprint $table) {
            $table->text('base_name')->after('note')->nullable(true);
            $table->text('base_path')->after('note')->nullable(true);
            $table->text('parent_path')->after('note')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tool_categories', function (Blueprint $table) {
            $table->dropColumn('base_name');
            $table->dropColumn('base_path');
            $table->dropColumn('parent_path');
        });
    }
}
