<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_upload_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('candidate_name', 255)->nullable();
            $table->string('otp', 6);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['email', 'used', 'expires_at']);
        });

        // Cleanup old OTPs periodically
        DB::statement("CREATE INDEX idx_otps_expires ON document_upload_otps (expires_at)");
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('document_upload_otps');
        Schema::enableForeignKeyConstraints();
    }
};
