<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['phone']);
            // Add index for fast lookup instead
            $table->index('email');
            $table->index('phone');
            $table->index(['email', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('phone');
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['email', 'phone']);
        });
    }
};