<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewCustomTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('web_link')->nullable(true);
            $table->text('title_catalog_class')->nullable(true);
            $table->text('title_product_class')->nullable(true);
            $table->text('domain_origin')->nullable(true);
            $table->text('page_catalog_class')->nullable(true);
            $table->text('last_page_catalog_class')->nullable(true);
            $table->text('page_string')->nullable(true);
            $table->integer('last_page_catalog_number')->nullable(true);
            $table->text('page_exclude_string')->nullable(true);
            $table->text('image_class')->nullable(true);
            $table->text('element_link')->nullable(true);
            $table->text('attr_link')->nullable(true);
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
        Schema::dropIfExists('custom_templates');
    }
}
