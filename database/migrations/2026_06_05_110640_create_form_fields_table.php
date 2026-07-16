<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('registration_forms')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_label');
            $table->string('field_placeholder')->nullable();
            $table->enum('field_type', ['text','email','phone','date','textarea','dropdown','radio','checkbox','file','number'])->default('text');
            $table->boolean('is_mandatory')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};