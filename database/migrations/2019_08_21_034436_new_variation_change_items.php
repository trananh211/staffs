<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewVariationChangeItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variation_change_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('variation_change_id');
            $table->string('variation_old_slug',255);
            $table->string('variation_old',255);
            $table->string('variation_compare',255);
            $table->string('variation_new',255);
            $table->string('variation_sku',100)->nullable(true);
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
        Schema::dropIfExists('variation_change_items');
    }
}
