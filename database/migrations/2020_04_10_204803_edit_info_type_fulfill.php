<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditInfoTypeFulfill extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_fulfills', function (Blueprint $table) {
            $table->dropColumn('web_path');
            $table->integer('tool_category_id')->after('working_file_id');
            $table->text('web_path_folder')->after('path');
            $table->text('web_path_file')->after('path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('file_fulfills', function (Blueprint $table) {
            $table->text('web_path')->after('path')->nullable(true);
            $table->dropColumn('tool_category_id');
            $table->dropColumn('web_path_folder');
            $table->dropColumn('web_path_file');
        });
    }
}
