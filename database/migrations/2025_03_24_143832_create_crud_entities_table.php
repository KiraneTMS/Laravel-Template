<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('crud_entities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->string('model_class');
            $table->string('table_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_entities');
    }
};
