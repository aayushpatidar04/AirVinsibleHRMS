<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name');
            $table->string('type'); // email, sms, biometric, payroll, accounting, etc.
            $table->string('provider'); // sendgrid, twilio, zakya, quickbooks, etc.
            $table->text('config')->nullable(); // JSON config
            $table->string('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
