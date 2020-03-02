<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditTagIdWooFolderDrivers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_folder_drivers', function (Blueprint $table) {
            $table->string('category_name',255)->after('template_id')->nullable(true);
            $table->integer('category_id')->after('category_name')->nullable(true);
            $table->integer('woo_category_id')->after('category_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_folder_drivers', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropColumn('category_id');
            $table->dropColumn('woo_category_id');
        });
    }
}
