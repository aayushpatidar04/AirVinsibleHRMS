<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old simple unique index if it exists (created by older schema versions).
        // The current original migration creates candidate_offers_number_version_unique instead.
        $indexes = DB::select(
            "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='candidate_offers' AND name='candidate_offers_offer_number_unique'"
        );

        if (count($indexes) > 0) {
            Schema::table('candidate_offers', function (Blueprint $table) {
                $table->dropUnique('candidate_offers_offer_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('candidate_offers', function (Blueprint $table) {
            $table->dropUnique('candidate_offers_number_version_unique');
            $table->unique('offer_number');
        });
    }
};
