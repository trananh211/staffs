<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWebsites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('store_id')->nullable(false);
            $table->integer('woo_category_id')->nullable(true);
            $table->integer('platform_id')->default(0);
            $table->text('exclude_text')->nullable(true);
            $table->text('url')->nullable(false);
            $table->timestamps();
        });
        \DB::statement('ALTER TABLE websites AUTO_INCREMENT = 1001;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websites');
    }
}
