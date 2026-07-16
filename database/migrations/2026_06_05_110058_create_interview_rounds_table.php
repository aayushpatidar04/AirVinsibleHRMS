<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('interview_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sequence_number');
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_hr_round')->default(false);  // HR round flag for salary offer
            $table->boolean('is_ops_round')->default(false); // OPS round flag
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('sequence_number');
        });
    }
    public function down(): void { Schema::dropIfExists('interview_rounds'); }
};