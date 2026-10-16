<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidate_intent_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('letter_number')->nullable()->unique();
            $table->string('status', 32)->default('draft')->index();
            $table->string('position_title');
            $table->string('work_location');
            $table->decimal('monthly_ctc', 14, 2);
            $table->decimal('monthly_in_hand', 14, 2);
            $table->date('joining_date');
            $table->date('letter_date');
            $table->string('pdf_path')->nullable();
            $table->string('acknowledged_pdf_path')->nullable();
            $table->string('sent_to_email')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->foreignId('sent_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('send_count')->default(0);
            $table->string('portal_token_hash', 64)->nullable()->unique();
            $table->timestamp('portal_token_expires_at')->nullable();
            $table->string('acknowledgement_name')->nullable();
            $table->longText('acknowledgement_text')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgement_ip', 45)->nullable();
            $table->text('acknowledgement_user_agent')->nullable();
            $table->boolean('candidate_consent')->default(false);
            $table->timestamps();

            $table->index(
                ['candidate_id', 'status'],
                'candidate_intent_letters_candidate_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_intent_letters');
    }
};
