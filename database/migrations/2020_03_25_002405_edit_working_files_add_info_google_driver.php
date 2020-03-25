<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWorkingFilesAddInfoGoogleDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('working_files', function (Blueprint $table) {
            $table->text('base_name')->after('name')->nullable(true);
            $table->text('base_path')->after('base_name')->nullable(true);
            $table->text('base_dirname')->after('path')->nullable(true);
            $table->smallInteger('is_fulfill')->after('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('working_files', function (Blueprint $table) {
            $table->dropColumn('base_name');
            $table->dropColumn('base_path');
            $table->dropColumn('base_dirname');
            $table->dropColumn('is_fulfill');
        });
    }
}
