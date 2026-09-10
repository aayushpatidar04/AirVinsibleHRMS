<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'candidate_offers',
            function (Blueprint $table) {
                $table
                    ->string('sent_to_email')
                    ->nullable()
                    ->after('sent_at');

                $table
                    ->string('email_subject')
                    ->nullable()
                    ->after('sent_to_email');

                $table
                    ->longText('email_message')
                    ->nullable()
                    ->after('email_subject');

                $table
                    ->json('email_cc')
                    ->nullable()
                    ->after('email_message');

                $table
                    ->unsignedInteger('send_count')
                    ->default(0)
                    ->after('email_cc');

                $table
                    ->foreignId('sent_by')
                    ->nullable()
                    ->after('send_count')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'candidate_offers',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId(
                    'sent_by'
                );

                $table->dropColumn([
                    'sent_to_email',
                    'email_subject',
                    'email_message',
                    'email_cc',
                    'send_count',
                ]);
            }
        );
    }
};