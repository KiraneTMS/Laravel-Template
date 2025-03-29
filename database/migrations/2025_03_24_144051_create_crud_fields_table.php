<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crud_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crud_entity_id')->constrained('crud_entities')->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->string('label');
            $table->string('visible_to_roles')->default('admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_fields');
    }
};
