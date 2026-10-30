<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        DB::table('document_requirements')->insert([
            ['name' => 'Identity Proof', 'slug' => 'identity_proof', 'description' => 'Aadhaar Card, Passport, PAN Card, or Driving License', 'is_required' => true, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Educational Certificate', 'slug' => 'educational_certificate', 'description' => 'Marksheet or Degree Certificate of the highest qualification', 'is_required' => true, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Previous Experience Letter', 'slug' => 'previous_experience_letter', 'description' => 'Experience letter or relieving letter from the previous employer', 'is_required' => false, 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Previous Offer Letter', 'slug' => 'previous_offer_letter', 'description' => 'Previous offer letter or appointment letter (if applicable)', 'is_required' => false, 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Passport Size Photograph', 'slug' => 'passport_size_photograph', 'description' => 'Recent passport size photograph', 'is_required' => true, 'sort_order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('document_requirements');
        Schema::enableForeignKeyConstraints();
    }
};
