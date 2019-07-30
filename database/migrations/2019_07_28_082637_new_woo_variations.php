<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewWooVariations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woo_variations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('variation_id');
            $table->integer('woo_template_id');
            $table->integer('template_id');
            $table->integer('store_id');
            $table->string('variation_path',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woo_variations');
    }
}
