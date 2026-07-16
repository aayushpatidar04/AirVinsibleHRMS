<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->string('position_applied');
            // Profile category drives salary offer logic:
            // advisory_executive = mandatory salary in HR round
            // leadership = salary discussion after OPS round
            $table->string('profile_category')->default('other');
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('current_round_id')->nullable()->constrained('interview_rounds')->nullOnDelete();
            $table->foreignId('current_interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('current_status', [
                'new','in_progress','round_completed','all_rounds_cleared','rejected'
            ])->default('new');
            $table->enum('final_status', ['pending','selected','not_selected'])->default('pending');
            $table->text('hiring_notes')->nullable();
            $table->decimal('final_salary_offered', 10, 2)->nullable();
            $table->string('final_designation')->nullable();
            $table->timestamp('registration_date')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'current_status']);
            $table->index(['profile_category', 'final_status']);
        });
    }
    public function down(): void { Schema::dropIfExists('candidates'); }
};