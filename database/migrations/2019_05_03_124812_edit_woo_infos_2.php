<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditWooInfos2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woo_infos', function (Blueprint $table) {
            $table->string('password', 30)->after('email');
            $table->string('host', 30)->after('password');
            $table->smallInteger('port')->after('host');
            $table->string('security',10)->after('port');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woo_infos', function (Blueprint $table) {
            $table->dropColumn('password');
            $table->dropColumn('host');
            $table->dropColumn('port');
            $table->dropColumn('security');
        });
    }
}
