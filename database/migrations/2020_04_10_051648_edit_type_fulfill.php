<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditTypeFulfill extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tool_categories', function (Blueprint $table) {
            $table->smallInteger('type_fulfill_id')->after('name')->default(1);
            $table->string('exclude_text',20)->after('type_fulfill_id')->nullable(true);
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
            $table->dropColumn('type_fulfill_id');
            $table->dropColumn('exclude_text');
        });
    }
}
