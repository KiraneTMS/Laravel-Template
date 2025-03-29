<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crud_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crud_field_id')->constrained('crud_fields')->onDelete('cascade');
            $table->string('rule');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_validations');
    }
};
