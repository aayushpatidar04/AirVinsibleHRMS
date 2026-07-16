<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('parent_field_id')
                ->nullable()
                ->after('form_id')
                ->constrained('form_fields')
                ->cascadeOnDelete();
            
            $table->json('show_when')
                ->nullable()
                ->after('parent_field_id')
                ->comment('JSON: {"parent_value": ["option1", "option2"]}');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['parent_field_id']);
            $table->dropColumn(['parent_field_id', 'show_when']);
        });
    }
};