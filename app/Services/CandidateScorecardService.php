<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Question;
use Illuminate\Support\Collection;

class CandidateScorecardService
{
    public function forCandidate(Candidate $candidate): array
    {
        $progresses = $candidate->roundProgress()
            ->with([
                'round',
                'responses.question',
                'customQuestions',
            ])
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        $categoryScores = collect();
        $roundScores = collect();

        foreach ($progresses as $progress) {
            $roundQuestionScores = collect();

            foreach ($progress->responses as $response) {
                $score = $this->resolveScore(
                    $response->question_rating,
                    $response->rating_value
                );

                $category = $response->question?->score_category;

                if ($score === null) {
                    continue;
                }

                $roundQuestionScores->push($score);

                if ($category) {
                    $categoryScores->push([
                        'category' => $category,
                        'score' => $score,
                    ]);
                }
            }

            foreach ($progress->customQuestions as $customQuestion) {
                $score = $this->resolveScore(
                    $customQuestion->question_rating,
                    $customQuestion->rating_value
                );

                if ($score === null) {
                    continue;
                }

                $roundQuestionScores->push($score);

                if ($customQuestion->score_category) {
                    $categoryScores->push([
                        'category' => $customQuestion->score_category,
                        'score' => $score,
                    ]);
                }
            }

            $roundScores->push([
                'progress_id' => $progress->id,
                'round_id' => $progress->round_id,
                'round_name' => $progress->round?->name ?? 'Unknown Round',

                'average' => $roundQuestionScores->isNotEmpty()
                    ? round($roundQuestionScores->average(), 2)
                    : null,

                'overall_rating' => $progress->overall_rating !== null
                    ? (float) $progress->overall_rating
                    : null,

                'rated_questions' => $roundQuestionScores->count(),
            ]);
        }

        $categories = $categoryScores
            ->groupBy('category')
            ->map(function (Collection $scores, string $category) {
                return [
                    'key' => $category,

                    'label' => Question::SCORE_CATEGORIES[$category]
                        ?? ucwords(str_replace('_', ' ', $category)),

                    'average' => round(
                        $scores->avg('score'),
                        2
                    ),

                    'responses_count' => $scores->count(),
                ];
            })
            ->values();

        $allQuestionScores = $categoryScores->pluck('score');

        $overallAverage = $allQuestionScores->isNotEmpty()
            ? round($allQuestionScores->average(), 2)
            : $this->averageRoundRatings($progresses);

        return [
            'overall_average' => $overallAverage,

            'recommendation' => $this->recommendation(
                $overallAverage
            ),

            'categories' => $categories,

            'rounds' => $roundScores->values(),

            'total_rated_questions' => $allQuestionScores->count(),

            'completed_rounds' => $progresses->count(),
        ];
    }

    private function resolveScore(
        mixed $questionRating,
        mixed $ratingValue
    ): ?float {
        $score = $questionRating ?? $ratingValue;

        if ($score === null || $score === '') {
            return null;
        }

        $score = (float) $score;

        if ($score < 1 || $score > 5) {
            return null;
        }

        return $score;
    }

    private function averageRoundRatings(
        Collection $progresses
    ): ?float {
        $ratings = $progresses
            ->pluck('overall_rating')
            ->filter(
                fn ($rating) =>
                    $rating !== null &&
                    $rating !== ''
            )
            ->map(fn ($rating) => (float) $rating);

        return $ratings->isNotEmpty()
            ? round($ratings->average(), 2)
            : null;
    }

    private function recommendation(
        ?float $average
    ): ?array {
        if ($average === null) {
            return null;
        }

        return match (true) {
            $average >= 4.5 => [
                'key' => 'strong_hire',
                'label' => 'Strong Hire',
                'tone' => 'success',
            ],

            $average >= 3.75 => [
                'key' => 'hire',
                'label' => 'Hire',
                'tone' => 'success',
            ],

            $average >= 3.0 => [
                'key' => 'consider',
                'label' => 'Consider',
                'tone' => 'warning',
            ],

            $average >= 2.0 => [
                'key' => 'weak',
                'label' => 'Weak Candidate',
                'tone' => 'danger',
            ],

            default => [
                'key' => 'no_hire',
                'label' => 'No Hire',
                'tone' => 'danger',
            ],
        };
    }
}