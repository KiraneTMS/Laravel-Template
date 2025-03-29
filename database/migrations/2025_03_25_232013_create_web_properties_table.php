<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebPropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('web_properties', function (Blueprint $table) {
            $table->id();
            $table->string('webname');
            $table->string('style');
            $table->string('icon')->nullable();
            $table->string('welcome_msg')->nullable();
            $table->json('color_scheme')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->json('packages')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_properties');
    }
}
