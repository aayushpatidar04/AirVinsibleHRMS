<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\AcceptCandidateOfferRequest;
use App\Http\Requests\Recruitment\DeclineCandidateOfferRequest;
use App\Models\CandidateOffer;
use App\Models\OfferSalaryComponent;
use App\Services\Recruitment\OfferPortalTokenService;
use App\Services\Recruitment\OfferSalaryCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateOfferPortalController extends Controller
{
    public function __construct(
        private readonly OfferPortalTokenService $tokenService,
        private readonly OfferSalaryCalculator $salaryCalculator,
    ) {
    }

    public function show(
        Request $request,
        string $token
    ): Response {
        $offer = $this->resolveOffer($token);

        $offer->loadMissing([
            'candidate',
            'branch',
            'reportingManager',
            'salaryComponents' => fn($query) =>
                $query
                    ->where('show_in_offer', true)
                    ->orderBy('sort_order'),
            'salaryComponents.percentageOfComponent',
        ]);

        $this->markViewed(
            $offer,
            $request
        );

        $calculation = $this->calculateSalary(
            $offer
        );

        return Inertia::render(
            'Public/OfferPortal/Show',
            [
                'company' =>
                    $this->companyPayload(),

                'candidate' => [
                    'name' =>
                        $offer->candidate->full_name
                        ?? trim(
                            ($offer->candidate->first_name ?? '')
                            . ' ' .
                            ($offer->candidate->last_name ?? '')
                        ),

                    'first_name' =>
                        $offer->candidate->first_name
                        ?? $offer->candidate->full_name,

                    'email' =>
                        $offer->candidate->email,
                ],

                'offer' =>
                    $this->offerPayload(
                        $offer,
                        $calculation
                    ),

                'portal' => [
                    'can_respond' =>
                        $offer->canReceiveCandidateResponse(),

                    'is_expired' =>
                        $offer->isExpiredForCandidate(),

                    'has_responded' =>
                        filled($offer->responded_at),

                    'response' =>
                        $offer->status ===
                        CandidateOffer::STATUS_ACCEPTED
                        ? 'accepted'
                        : (
                            $offer->status ===
                            CandidateOffer::STATUS_DECLINED
                            ? 'declined'
                            : null
                        ),

                    'routes' => [
                        'accept' => route(
                            'candidate-offers.portal.accept',
                            $token
                        ),

                        'decline' => route(
                            'candidate-offers.portal.decline',
                            $token
                        ),

                        'download_pdf' =>
                            $offer->pdf_path
                            ? route(
                                'candidate-offers.portal.download-pdf',
                                $token
                            )
                            : null,
                    ],
                ],
            ]
        );
    }

    public function accept(
        AcceptCandidateOfferRequest $request,
        string $token
    ): RedirectResponse {
        $offer = $this->resolveOffer($token);

        if (!$offer->canReceiveCandidateResponse()) {
            return back()->with(
                'error',
                'This offer can no longer be accepted.'
            );
        }

        DB::transaction(
            function () use ($offer, $request): void {
                $offer->forceFill([
                    'status' =>
                        CandidateOffer::STATUS_ACCEPTED,

                    'accepted_at' =>
                        now(),

                    'declined_at' =>
                        null,

                    'responded_at' =>
                        now(),

                    'candidate_response_name' =>
                        $request->validated(
                            'candidate_name'
                        ),

                    'candidate_consent' =>
                        true,

                    'response_ip' =>
                        $request->ip(),

                    'response_user_agent' =>
                        substr(
                            (string) $request->userAgent(),
                            0,
                            2000
                        ),

                    'decline_reason' =>
                        null,
                ])->save();
            }
        );

        return back()->with(
            'success',
            'Thank you. Your offer has been accepted successfully.'
        );
    }

    public function decline(
        DeclineCandidateOfferRequest $request,
        string $token
    ): RedirectResponse {
        $offer = $this->resolveOffer($token);

        if (!$offer->canReceiveCandidateResponse()) {
            return back()->with(
                'error',
                'This offer can no longer be declined.'
            );
        }

        DB::transaction(
            function () use ($offer, $request): void {
                $validated =
                    $request->validated();

                $offer->forceFill([
                    'status' =>
                        CandidateOffer::STATUS_DECLINED,

                    'declined_at' =>
                        now(),

                    'accepted_at' =>
                        null,

                    'responded_at' =>
                        now(),

                    'candidate_response_name' =>
                        $validated['candidate_name'],

                    'candidate_consent' =>
                        true,

                    'response_ip' =>
                        $request->ip(),

                    'response_user_agent' =>
                        substr(
                            (string) $request->userAgent(),
                            0,
                            2000
                        ),

                    'decline_reason' =>
                        $validated['reason'],
                ])->save();
            }
        );

        return back()->with(
            'success',
            'Your response has been recorded.'
        );
    }

    public function downloadPdf(
        string $token
    ): StreamedResponse {
        $offer = $this->resolveOffer($token);

        abort_unless(
            $offer->pdf_path &&
            Storage::disk('public')->exists(
                $offer->pdf_path
            ),
            404,
            'The offer PDF is not available.'
        );

        return Storage::disk('public')->download(
            $offer->pdf_path,
            sprintf(
                '%s-v%s.pdf',
                $offer->offer_number
                ?: 'offer-letter',
                $offer->version ?: 1
            ),
            [
                'Content-Type' =>
                    'application/pdf',

                'Cache-Control' =>
                    'private, no-store, max-age=0',
            ]
        );
    }

    private function resolveOffer(
        string $token
    ): CandidateOffer {
        $offer =
            $this->tokenService
                ->findByPlainToken($token);

        abort_unless(
            $offer &&
            $offer->canBeViewedByCandidate(),
            404
        );

        return $offer;
    }

    private function markViewed(
        CandidateOffer $offer,
        Request $request
    ): void {
        if ($offer->viewed_at) {
            return;
        }

        $attributes = [
            'viewed_at' =>
                now(),

            'viewed_ip' =>
                $request->ip(),

            'viewed_user_agent' =>
                substr(
                    (string) $request->userAgent(),
                    0,
                    2000
                ),
        ];

        if (
            $offer->status ===
            CandidateOffer::STATUS_SENT
        ) {
            $attributes['status'] =
                CandidateOffer::STATUS_VIEWED;
        }

        $offer->forceFill(
            $attributes
        )->save();
    }

    private function calculateSalary(
        CandidateOffer $offer
    ): array {
        return $this->salaryCalculator->calculate(
            $offer->salaryComponents
                ->map(
                    fn(
                    OfferSalaryComponent $component
                ) => [
                        'id' =>
                            $component->id,

                        'row_key' =>
                            $component->row_key,

                        'component_name' =>
                            $component->component_name,

                        'component_type' =>
                            $component->component_type,

                        'calculation_type' =>
                            $component->calculation_type,

                        'amount' =>
                            $component->amount,

                        'percentage' =>
                            $component->percentage,

                        'percentage_of_component_id' =>
                            $component
                                ->percentage_of_component_id,

                        'percentage_of_row_key' =>
                            $component
                                ->percentageOfComponent
                                    ?->row_key,

                        'frequency' =>
                            $component->frequency,

                        'affects_in_hand' =>
                            $component->affects_in_hand,

                        'is_taxable' =>
                            $component->is_taxable,

                        'show_in_offer' =>
                            $component->show_in_offer,
                    ]
                )
                ->values()
                ->all()
        );
    }

    private function companyPayload(): array
    {
        return [
            'name' =>
                config(
                    'company.name',
                    config('app.name')
                ),

            'legal_name' =>
                config(
                    'company.legal_name',
                    config('app.name')
                ),

            'logo_url' =>
                asset(
                    config(
                        'company.logo_path',
                        'images/company-logo.png'
                    )
                ),

            'email' =>
                config(
                    'recruitment.offer_portal.support_email'
                ),

            'phone' =>
                config(
                    'recruitment.offer_portal.support_phone'
                ),

            'website' =>
                config('company.website'),

            'address' =>
                config('company.address'),
        ];
    }

    private function offerPayload(
        CandidateOffer $offer,
        array $calculation
    ): array {
        $calculatedComponents =
            collect(
                $calculation['components']
                ?? []
            )->keyBy(
                    fn(array $component) =>
                    $component['row_key']
                    ?? $component['id']
                );

        return [
            'offer_number' =>
                $offer->offer_number,

            'version' =>
                $offer->version ?? 1,

            'status' =>
                $offer->status,

            'designation' =>
                $offer->designation
                ?? '—',

            'department' =>
                $offer->department
                ?? '—',

            'branch' =>
                $offer->branch?->name
                ?? '—',

            'reporting_manager' =>
                $offer->reportingManager?->name
                ?? '—',

            'employment_type' =>
                $offer->employment_type,

            'work_location' =>
                $offer->work_location,

            'process' =>
                $offer->process,

            'joining_date' =>
                optional(
                    $offer->joining_date
                )?->format('Y-m-d'),

            'reporting_time' =>
                $offer->reporting_time,

            'probation_months' =>
                $offer->probation_months,

            'notice_period_days' =>
                $offer->notice_period_days,

            'valid_from' =>
                optional(
                    $offer->valid_from
                )?->format('Y-m-d'),

            'valid_till' =>
                optional(
                    $offer->valid_till
                )?->format('Y-m-d'),

            'currency' =>
                $offer->currency ?? 'INR',

            'offer_terms' =>
                $offer->offer_terms,

            'salary_annexure' =>
                $offer->salary_annexure,

            'accepted_at' =>
                optional(
                    $offer->accepted_at
                )?->toISOString(),

            'declined_at' =>
                optional(
                    $offer->declined_at
                )?->toISOString(),

            'responded_at' =>
                optional(
                    $offer->responded_at
                )?->toISOString(),

            'salary_summary' => [
                'monthly_gross' =>
                    $calculation[
                        'monthly_gross'
                    ] ?? 0,

                'monthly_deductions' =>
                    $calculation[
                        'monthly_deductions'
                    ] ?? 0,

                'monthly_employer_contributions' =>
                    $calculation[
                        'monthly_employer_contributions'
                    ] ?? 0,

                'monthly_in_hand' =>
                    $calculation[
                        'monthly_in_hand'
                    ] ?? 0,

                'annual_gross' =>
                    $calculation[
                        'annual_gross'
                    ] ?? 0,

                'annual_ctc' =>
                    $calculation[
                        'annual_ctc'
                    ] ?? 0,
            ],

            'salary_components' =>
                $offer->salaryComponents
                    ->map(
                        function (OfferSalaryComponent $component) use ($calculatedComponents): array {
                            $calculated =
                                $calculatedComponents->get(
                                    $component->row_key
                                    ?? $component->id,
                                    []
                                );

                            return [
                                'id' =>
                                    $component->id,

                                'name' =>
                                    $component->component_name,

                                'type' =>
                                    $component->component_type,

                                'calculation_type' =>
                                    $component->calculation_type,

                                'frequency' =>
                                    $component->frequency,

                                'description' =>
                                    $component->description,

                                'monthly_amount' =>
                                    (float) (
                                        $calculated[
                                            'monthly_amount'
                                        ] ?? 0
                                    ),

                                'annual_amount' =>
                                    (float) (
                                        $calculated[
                                            'annual_amount'
                                        ] ?? 0
                                    ),
                            ];
                        }
                    )
                    ->values()
                    ->all(),
        ];
    }
}