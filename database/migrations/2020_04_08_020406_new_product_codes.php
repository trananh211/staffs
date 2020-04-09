<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewProductCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_code',100);
            $table->text('base_name')->nullable(true);
            $table->text('base_path')->nullable(true);
            $table->text('parent_path')->nullable(true);
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('product_codes');
    }
}
