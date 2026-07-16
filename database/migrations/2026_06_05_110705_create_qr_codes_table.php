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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('label');
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('form_id')->constrained('registration_forms')->restrictOnDelete();
            $table->timestamp('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};