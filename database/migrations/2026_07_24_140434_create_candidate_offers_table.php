<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidate_offers', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Candidate
            |--------------------------------------------------------------------------
            */

            $table->foreignId('candidate_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Offer Identification
            |--------------------------------------------------------------------------
            */

            $table->string('offer_number')->unique();

            /*
             * Incremented whenever a new offer revision is prepared.
             *
             * Example:
             * Version 1 = original offer
             * Version 2 = revised salary
             */
            $table->unsignedSmallInteger('version')
                ->default(1);

            /*
             * Supported statuses:
             *
             * draft
             * pending_approval
             * approved
             * sent
             * viewed
             * accepted
             * declined
             * expired
             * cancelled
             */
            $table->string('status', 40)
                ->default('draft')
                ->index();

            /*
             * Secure token for candidate-facing offer links.
             *
             * It will be generated before sending the offer.
             */
            $table->uuid('public_token')
                ->nullable()
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Employment Details
            |--------------------------------------------------------------------------
            */

            $table->string('process_name')->nullable();

            $table->string('designation')->nullable();

            $table->string('department')->nullable();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            /*
             * The reporting manager can be selected from the users table.
             */
            $table->foreignId('reporting_manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Suggested values:
             *
             * full_time
             * part_time
             * contract
             * internship
             * consultant
             */
            $table->string('employment_type', 50)
                ->default('full_time');

            $table->unsignedTinyInteger('probation_months')
                ->nullable();

            $table->date('joining_date')
                ->nullable();

            $table->time('reporting_time')
                ->nullable();

            $table->string('work_location')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Compensation
            |--------------------------------------------------------------------------
            |
            | Decimal values are used instead of integers so future salary
            | structures can support paise or calculated values.
            |--------------------------------------------------------------------------
            */

            /*
             * Total CTC offered.
             *
             * The UI should clearly mention whether this is monthly or annual.
             * We will standardize that in the Offer Builder.
             */
            $table->decimal('salary_ctc', 14, 2)
                ->nullable();

            /*
             * Employee's expected in-hand amount.
             */
            $table->decimal('salary_in_hand', 14, 2)
                ->nullable();

            $table->decimal('salary_basic', 14, 2)
                ->nullable();

            $table->decimal('salary_hra', 14, 2)
                ->nullable();

            $table->decimal('salary_special_allowance', 14, 2)
                ->nullable();

            $table->decimal('salary_pf', 14, 2)
                ->nullable();

            $table->decimal('salary_bonus', 14, 2)
                ->nullable();

            $table->decimal('salary_variable', 14, 2)
                ->nullable();

            $table->decimal('salary_other_allowances', 14, 2)
                ->nullable();

            /*
             * Indicates whether PF is part of the final approved offer.
             */
            $table->boolean('pf_allowed')
                ->default(false);

            /*
             * Stores additional dynamic salary components.
             *
             * Example:
             *
             * [
             *     {
             *         "name": "Attendance Incentive",
             *         "amount": 1500,
             *         "frequency": "monthly"
             *     }
             * ]
             */
            $table->json('salary_structure_json')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Offer Content
            |--------------------------------------------------------------------------
            */

            /*
             * Rich-text salary structure or pasted Excel table.
             */
            $table->longText('salary_annexure')
                ->nullable();

            /*
             * Rich-text offer clauses, joining conditions and policies.
             */
            $table->longText('offer_terms')
                ->nullable();

            /*
             * Internal remarks not visible to the candidate.
             */
            $table->text('internal_remarks')
                ->nullable();

            /*
             * Final date until which the candidate can accept the offer.
             */
            $table->date('offer_valid_till')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | PDF
            |--------------------------------------------------------------------------
            */

            /*
             * Storage-relative path.
             *
             * Example:
             * offers/2026/OFR-2026-00001-v1.pdf
             */
            $table->string('pdf_path')
                ->nullable();

            $table->timestamp('pdf_generated_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Candidate Activity
            |--------------------------------------------------------------------------
            */

            $table->timestamp('sent_at')
                ->nullable();

            $table->timestamp('viewed_at')
                ->nullable();

            $table->timestamp('accepted_at')
                ->nullable();

            $table->timestamp('declined_at')
                ->nullable();

            $table->timestamp('expired_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('decline_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit Columns
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['candidate_id', 'status'],
                'candidate_offers_candidate_status_index'
            );

            $table->index(
                ['candidate_id', 'version'],
                'candidate_offers_candidate_version_index'
            );

            $table->index(
                ['status', 'offer_valid_till'],
                'candidate_offers_status_validity_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_offers');
    }
};