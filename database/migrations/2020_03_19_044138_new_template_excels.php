<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewTemplateExcels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_excels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key_title',100);
            $table->string('title',100);
            $table->string('fixed',100)->nullable(true);
            $table->integer('tool_category_id');
            $table->smallInteger('sort');
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
        Schema::dropIfExists('template_excels');
    }
}
