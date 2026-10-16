<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_offers', function (Blueprint $table) {
            $table->dropUnique('candidate_offers_offer_number_unique');
            $table->unique(
                ['offer_number', 'version'],
                'candidate_offers_number_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('candidate_offers', function (Blueprint $table) {
            $table->dropUnique('candidate_offers_number_version_unique');
            $table->unique('offer_number');
        });
    }
};
