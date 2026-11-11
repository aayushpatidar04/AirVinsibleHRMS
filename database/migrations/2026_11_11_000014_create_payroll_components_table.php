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
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name'); // Basic Salary, HRA, Conveyance, PF, etc.
            $table->string('code')->unique(); // BASIC, HRA, CONV, PF, etc.
            $table->enum('type', ['earning', 'deduction']);
            $table->string('calculation_method')->default('fixed'); // fixed, percentage, formula
            $table->decimal('default_value', 15, 2)->default(0);
            $table->string('percentage_base')->nullable(); // basic, gross, etc.
            $table->boolean('is_statutory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('formula')->nullable(); // Calculation formula if applicable
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'type', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
