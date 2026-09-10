<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateRoundCustomQuestion;
use App\Models\CandidateRoundProgress;
use App\Models\Response;
use App\Models\User;
use Illuminate\Support\Collection;

class InterviewHistoryService
{
    public function forCandidate(
        Candidate $candidate,
        ?User $viewer = null
    ): Collection {
        return $candidate->roundProgress()
            ->with([
                'round',
                'interviewer',

                'responses' => fn ($query) =>
                    $query->orderBy('id'),

                'responses.question',

                'customQuestions' => fn ($query) =>
                    $query->orderBy('order')
                        ->orderBy('id'),

                'customQuestions.interviewer',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(
                fn (CandidateRoundProgress $progress) =>
                    $this->mapProgress(
                        $progress,
                        $viewer
                    )
            )
            ->values();
    }

    private function mapProgress(
        CandidateRoundProgress $progress,
        ?User $viewer
    ): array {
        $templateResponses = $progress->responses
            ->map(
                fn (Response $response) =>
                    $this->mapTemplateResponse($response)
            )
            ->values();

        $customQuestions = $progress->customQuestions
            ->map(
                fn (
                    CandidateRoundCustomQuestion $question
                ) => $this->mapCustomQuestion($question)
            )
            ->values();

        return [
            'id' => $progress->id,
            'round_id' => $progress->round_id,

            'round_name' =>
                $progress->round?->name ?? 'Deleted Round',

            'round_sequence' =>
                $progress->round?->sequence_number,

            'is_hr_round' =>
                (bool) $progress->round?->is_hr_round,

            'is_ops_round' =>
                (bool) $progress->round?->is_ops_round,

            'interviewer' =>
                $progress->interviewer?->full_name
                ?? 'Not assigned',

            'interviewer_id' =>
                $progress->interviewer_id,

            'interviewer_employee_id' =>
                $progress->interviewer?->employee_id,

            'is_mine' =>
                $viewer
                    ? (int) $progress->interviewer_id
                        === (int) $viewer->id
                    : false,

            'status' =>
                $progress->status,

            'started_at' =>
                $progress->start_date?->format(
                    'd M Y H:i'
                ),

            'ended_at' =>
                $progress->end_date?->format(
                    'd M Y H:i'
                ),

            'duration' =>
                $progress->getDurationLabel(),

            'rating' =>
                $progress->overall_rating,

            'rating_stars' =>
                $progress->getRatingStars(),

            'feedback' =>
                $progress->overall_feedback,

            'rejection_reason' =>
                $progress->rejection_reason,

            'salary_offer_min' =>
                $progress->salary_offer_min,

            'salary_offer_max' =>
                $progress->salary_offer_max,

            'offered_designation' =>
                $progress->offered_designation,

            'salary_offer_status' =>
                $progress->salary_offer_status,

            /*
             * Keep legacy key temporarily so current Vue pages continue
             * working without an immediate full rewrite.
             */
            'responses' =>
                $templateResponses,

            'template_responses' =>
                $templateResponses,

            'custom_questions' =>
                $customQuestions,

            'question_counts' => [
                'template' =>
                    $templateResponses->count(),

                'custom' =>
                    $customQuestions->count(),

                'total' =>
                    $templateResponses->count()
                    + $customQuestions->count(),
            ],
        ];
    }

    private function mapTemplateResponse(
        Response $response
    ): array {
        return [
            'key' => "template-{$response->id}",
            'source' => 'template',
            'response_id' => $response->id,

            'question_id' => $response->question_id,

            'question' => $response->question?->question_text
                ?? 'Deleted question',

            'type' => $response->question?->question_type,

            'score_category' => $response->question?->score_category,

            'is_mandatory' => (bool) $response->question?->is_mandatory,

            'response_text' => $response->response_text,

            'rating_value' => $response->rating_value,

            'yes_no_value' => $response->yes_no_value,

            'selected_options' => $response->selected_options ?? [],

            'interviewer_notes' => $response->interviewer_notes,

            'question_rating' => $response->question_rating,
        ];
    }

    private function mapCustomQuestion(
        CandidateRoundCustomQuestion $question
    ): array {
        return [
            'key' => "custom-{$question->id}",
            'source' => 'custom',
            'id' => $question->id,

            'question' =>
                $question->question_text,

            'type' =>
                $question->question_type,

            'is_mandatory' =>
                (bool) $question->is_mandatory,

            'added_by' =>
                $question->interviewer?->full_name
                ?? 'Unknown interviewer',

            'response_text' =>
                $question->response_text,

            'rating_value' =>
                $question->rating_value,

            'yes_no_value' =>
                $question->yes_no_value,

            'selected_options' =>
                $question->selected_options ?? [],

            'interviewer_notes' =>
                $question->interviewer_notes,

            'question_rating' =>
                $question->question_rating,
        ];
    }
}