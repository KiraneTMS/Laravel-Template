<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateproductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->decimal('product_id', 16, 2);
            $table->string('product_name');
            $table->decimal('price', 16, 2);
            $table->decimal('stock', 16, 2);
            $table->string('description');
            $table->string('image');


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}