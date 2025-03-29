<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crud_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crud_entity_id')->constrained('crud_entities')->onDelete('cascade');
            $table->string('field_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_columns');
    }
};
