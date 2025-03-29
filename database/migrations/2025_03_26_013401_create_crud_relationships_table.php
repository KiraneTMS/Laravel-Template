<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrudRelationshipsTable extends Migration
{
    public function up()
    {
        Schema::create('crud_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crud_entity_id');
            $table->string('type');
            $table->string('related_table');
            $table->string('foreign_key');
            $table->string('local_key')->default('id');
            $table->string('display_column')->nullable();
            $table->timestamps();

            $table->foreign('crud_entity_id')
                  ->references('id')
                  ->on('crud_entities')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crud_relationships');
    }
}
