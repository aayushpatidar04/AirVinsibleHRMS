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
                    ->string('portal_token_hash', 64)
                    ->nullable()
                    ->unique()
                    ->after('send_count');

                $table
                    ->timestamp('portal_token_created_at')
                    ->nullable()
                    ->after('portal_token_hash');

                $table
                    ->timestamp('portal_token_expires_at')
                    ->nullable()
                    ->after('portal_token_created_at');

                $table
                    ->timestamp('portal_token_revoked_at')
                    ->nullable()
                    ->after('portal_token_expires_at');

                $table
                    ->string('viewed_ip', 45)
                    ->nullable()
                    ->after('viewed_at');

                $table
                    ->text('viewed_user_agent')
                    ->nullable()
                    ->after('viewed_ip');

                $table
                    ->timestamp('responded_at')
                    ->nullable()
                    ->after('viewed_user_agent');

                $table
                    ->string('response_ip', 45)
                    ->nullable()
                    ->after('responded_at');

                $table
                    ->text('response_user_agent')
                    ->nullable()
                    ->after('response_ip');

                $table
                    ->boolean('candidate_consent')
                    ->default(false)
                    ->after('response_user_agent');

                $table
                    ->string('candidate_response_name')
                    ->nullable()
                    ->after('candidate_consent');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'candidate_offers',
            function (Blueprint $table) {
                $table->dropColumn([
                    'portal_token_hash',
                    'portal_token_created_at',
                    'portal_token_expires_at',
                    'portal_token_revoked_at',
                    'viewed_ip',
                    'viewed_user_agent',
                    'responded_at',
                    'response_ip',
                    'response_user_agent',
                    'candidate_consent',
                    'candidate_response_name',
                ]);
            }
        );
    }
};