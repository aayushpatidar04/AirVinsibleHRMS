<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\SaveCandidateOfferRequest;
use App\Http\Requests\Recruitment\CancelCandidateOfferRequest;
use App\Http\Requests\Recruitment\SendCandidateOfferRequest;
use App\Mail\CandidateOfferMail;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateOffer;
use App\Models\User;
use App\Models\OfferSalaryComponent;
use App\Services\Recruitment\OfferPdfService;
use App\Services\Recruitment\OfferSalaryCalculator;
use App\Services\Recruitment\OfferSalaryComponentService;
use App\Services\Recruitment\OfferPortalTokenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CandidateOfferController extends Controller
{

    public function __construct(
        private readonly OfferSalaryComponentService $salaryComponentService,
        private readonly OfferSalaryCalculator $salaryCalculator,
    ) {
    }

    /**
     * Display all candidate offers.
     */
    public function index(
        Request $request
    ): Response {
        $this->authorize(
            'viewAny',
            CandidateOffer::class
        );

        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                Rule::in(
                    array_keys(
                        CandidateOffer::statusOptions()
                    )
                ),
            ],

            'approval_status' => [
                'nullable',
                Rule::in(
                    array_keys(
                        CandidateOffer::approvalStatusOptions()
                    )
                ),
            ],

            'department' => [
                'nullable',
                'string',
            ],

            'designation' => [
                'nullable',
                'string',
            ],

            'employment_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'generated_by' => [
                'nullable',
                'integer',
            ],

            'created_from' => [
                'nullable',
                'date',
            ],

            'created_to' => [
                'nullable',
                'date',
                'after_or_equal:created_from',
            ],

            'valid_from' => [
                'nullable',
                'date',
            ],

            'valid_to' => [
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],

            'version' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    25,
                    50,
                    100,
                ]),
            ],
        ]);

        $offersQuery =
            CandidateOffer::query()
                ->with([
                    'candidate',
                    'generatedBy',
                ])
                ->latest('id');

        $this->applyIndexFilters(
            $offersQuery,
            $filters
        );

        $perPage =
            (int) (
                $filters['per_page']
                ?? 25
            );

        $offers =
            $offersQuery
                ->paginate($perPage)
                ->withQueryString()
                ->through(
                    fn(
                    CandidateOffer $offer
                ) =>
                    $this->offerIndexPayload(
                        $request,
                        $offer
                    )
                );

        return Inertia::render(
            'Recruitment/Offers/Index',
            [
                'offers' =>
                    $offers,

                'filters' =>
                    $filters,

                'statistics' =>
                    $this->offerStatistics(),

                'options' => [
                    'statuses' =>
                        CandidateOffer::statusOptions(),

                    'approval_statuses' =>
                        CandidateOffer::approvalStatusOptions(),

                    'employment_types' =>
                        CandidateOffer::employmentTypeOptions(),

                    'generators' =>
                        User::query()
                            ->whereHas(
                                'generatedCandidateOffers'
                            )
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'id'
                            ),
                ],

                'permissions' => [
                    'create' =>
                        $request->user()->can(
                            'create',
                            CandidateOffer::class
                        ),

                    'export' =>
                        $request->user()->can(
                            'export',
                            CandidateOffer::class
                        ),

                    'bulk_delete' =>
                        $request->user()->can(
                            'bulkDelete',
                            CandidateOffer::class
                        ),
                ],

                'routes' => [
                    'index' =>
                        route(
                            'recruitment.offers.index'
                        ),

                    // 'export' =>
                    //     route(
                    //         'recruitment.offers.export'
                    //     ),

                    'bulk_delete' =>
                        route(
                            'recruitment.offers.bulk-destroy'
                        ),
                ],
            ]
        );
    }

    /**
     * Open the Offer Builder for a selected candidate.
     */
    public function create(Candidate $candidate, Request $request): Response
    {
        Gate::authorize(
            'create',
            [CandidateOffer::class, $candidate]
        );

        $candidate->load([
            'latestOffer',
        ]);

        /*
         * Prevent accidental duplicate active offers.
         * Revisions must be created through createRevision().
         */
        $activeOffer = $candidate->offers()
            ->open()
            ->latestVersion()
            ->first();

        if ($activeOffer) {
            abort(
                409,
                'This candidate already has an active offer. Open the existing offer or create a revision.'
            );
        }

        return Inertia::render(
            'Recruitment/Offers/Create',
            [
                'candidate' =>
                    $this->candidatePayload(
                        $candidate
                    ),

                'defaults' => [
                    ...$this->offerDefaults(
                        $candidate
                    ),

                    'salary_components' =>
                        $this->defaultSalaryComponents(),
                ],

                'options' =>
                    $this->formOptions(),

                'permissions' => [
                    'create' =>
                        $request->user()->can(
                            'create',
                            [
                                CandidateOffer::class,
                                $candidate,
                            ]
                        ),
                ],

                'routes' => [
                    'store' =>
                        route(
                            'recruitment.candidates.offers.store',
                            $candidate
                        ),

                    'back' =>
                        route(
                            'recruitment.candidates.show',
                            $candidate
                        ),

                    'index' =>
                        route(
                            'recruitment.offers.index'
                        ),
                ],
            ]
        );
    }

    /**
     * Save a new offer draft.
     */
    public function store(
        SaveCandidateOfferRequest $request,
        Candidate $candidate
    ): RedirectResponse {
        Gate::authorize(
            'create',
            [CandidateOffer::class, $candidate]
        );

        $validated = $request->validated();

        $salaryComponents =
            $validated['salary_components'];

        unset(
            $validated['salary_components'],
            $validated['salary_ctc'],
            $validated['salary_in_hand']
        );

        $offer = DB::transaction(
            function () use ($request, $candidate, $validated, $salaryComponents) {
                $lockedCandidate =
                    Candidate::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $candidate->id
                        );

                $activeOfferExists =
                    CandidateOffer::query()
                        ->where(
                            'candidate_id',
                            $lockedCandidate->id
                        )
                        ->open()
                        ->exists();

                if ($activeOfferExists) {
                    abort(
                        409,
                        'An active offer already exists for this candidate.'
                    );
                }

                $offer =
                    CandidateOffer::create([
                        ...$validated,

                        'candidate_id' =>
                            $lockedCandidate->id,

                        'status' =>
                            CandidateOffer::STATUS_DRAFT,

                        'version' => 1,

                        'generated_by' =>
                            $request->user()->id,

                        'salary_ctc' => 0,

                        'salary_in_hand' => 0,
                    ]);

                $this->salaryComponentService
                    ->sync(
                        $offer,
                        $salaryComponents
                    );

                return $offer;
            }
        );

        return redirect()
            ->route(
                'recruitment.offers.show',
                $offer
            )
            ->with(
                'success',
                'Offer draft created successfully.'
            );
    }

    /**
     * Display one offer and its candidate context.
     */
    public function show(
        Request $request,
        CandidateOffer $candidateOffer
    ): Response {
        $this->authorize(
            'view',
            $candidateOffer
        );

        $candidateOffer->load([
            'candidate',
            'generatedBy',
            'approvedBy',
            'salaryComponents' => fn($query) =>
                $query->orderBy('sort_order'),
            'salaryComponents.percentageOfComponent',
        ]);

        $salaryCalculation =
            $this->salaryCalculator->calculate(
                $candidateOffer
                    ->salaryComponents
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

        $components = collect($salaryCalculation);

        $salarySummary = [
            'monthly_gross' => $components
                ->where('component_type', 'earning')
                ->sum('monthly_amount'),

            'monthly_deductions' => $components
                ->where('component_type', 'deduction')
                ->sum('monthly_amount'),

            'monthly_employer_contributions' => $components
                ->where('component_type', 'employer_contribution')
                ->sum('monthly_amount'),

            'monthly_in_hand' =>
                ($components->where('component_type', 'earning')->sum('monthly_amount'))
                - ($components->where('component_type', 'deduction')->sum('monthly_amount')),

            'annual_gross' => $components
                ->where('component_type', 'earning')
                ->sum('annual_amount'),

            'annual_ctc' =>
                ($components->where('component_type', 'earning')->sum('annual_amount'))
                + ($components->where('component_type', 'employer_contribution')->sum('annual_amount')),
        ];

        return Inertia::render(
            'Recruitment/Offers/Show',
            [
                'candidate' =>
                    $this->candidatePayload(
                        $candidateOffer->candidate
                    ),

                'offer' =>
                    $this->offerShowPayload(
                        $candidateOffer,
                        $salaryCalculation
                    ),

                'salarySummary' => $salarySummary,

                'versions' =>
                    $this->offerVersionsPayload(
                        $candidateOffer
                    ),

                'permissions' => [
                    'update' =>
                        $request->user()->can(
                            'update',
                            $candidateOffer
                        ),

                    'submit_for_approval' =>
                        $request->user()->can(
                            'submitForApproval',
                            $candidateOffer
                        ),

                    'approve' =>
                        $request->user()->can(
                            'approve',
                            $candidateOffer
                        ),

                    'reject' =>
                        $request->user()->can(
                            'reject',
                            $candidateOffer
                        ),

                    'generate_pdf' =>
                        $request->user()->can(
                            'generatePdf',
                            $candidateOffer
                        ),

                    'send' =>
                        $request->user()->can(
                            'send',
                            $candidateOffer
                        ),

                    'cancel' =>
                        $request->user()->can(
                            'cancel',
                            $candidateOffer
                        ),

                    'create_revision' =>
                        $request->user()->can(
                            'createRevision',
                            $candidateOffer
                        ),
                ],

                'routes' => [
                    'index' =>
                        route(
                            'recruitment.offers.index'
                        ),

                    'edit' =>
                        route(
                            'recruitment.offers.edit',
                            $candidateOffer
                        ),

                    'revision' =>
                        route(
                            'recruitment.offers.revisions.create',
                            $candidateOffer
                        ),

                    'submit_for_approval' =>
                        route(
                            'recruitment.offers.submit-for-approval',
                            $candidateOffer
                        ),

                    'approve' =>
                        route(
                            'recruitment.offers.approve',
                            $candidateOffer
                        ),

                    'reject' =>
                        route(
                            'recruitment.offers.destroy',
                            $candidateOffer
                        ),

                    'generate_pdf' => Route::has(
                        'recruitment.offers.generate-pdf'
                    )
                        ? route(
                            'recruitment.offers.generate-pdf',
                            $candidateOffer
                        )
                        : null,

                    'download_pdf' => (
                        $candidateOffer->pdf_path &&
                        Route::has(
                            'recruitment.offers.download-pdf'
                        )
                    )
                        ? route(
                            'recruitment.offers.download-pdf',
                            $candidateOffer
                        )
                        : null,

                    'send_create' =>
                        route(
                            'recruitment.offers.send.create',
                            $candidateOffer
                        ),

                    'send' =>
                        route(
                            'recruitment.offers.send',
                            $candidateOffer
                        ),

                    'cancel' =>
                        route(
                            'recruitment.offers.cancel',
                            $candidateOffer
                        ),
                ],
            ]
        );
    }

    public function edit(
        Request $request,
        CandidateOffer $candidateOffer
    ): Response {
        $this->authorize(
            'update',
            $candidateOffer
        );

        $candidateOffer->load([
            'candidate',
            'salaryComponents.percentageOfComponent',
        ]);

        return Inertia::render(
            'Recruitment/Offers/Edit',
            [
                'candidate' =>
                    $this->candidatePayload(
                        $candidateOffer->candidate
                    ),

                'offer' =>
                    $this->offerFormPayload(
                        $candidateOffer
                    ),

                'options' =>
                    $this->formOptions(),

                'permissions' => [
                    'update' =>
                        $request->user()->can(
                            'update',
                            $candidateOffer
                        ),

                    'submit_for_approval' =>
                        $request->user()->can(
                            'submitForApproval',
                            $candidateOffer
                        ),

                    'approve' =>
                        $request->user()->can(
                            'approve',
                            $candidateOffer
                        ),

                    'generate_pdf' =>
                        $request->user()->can(
                            'generatePdf',
                            $candidateOffer
                        ),

                    'send' =>
                        $request->user()->can(
                            'send',
                            $candidateOffer
                        ),

                    'cancel' =>
                        $request->user()->can(
                            'cancel',
                            $candidateOffer
                        ),

                    'create_revision' =>
                        $request->user()->can(
                            'createRevision',
                            $candidateOffer
                        ),
                ],

                'routes' => [
                    'update' =>
                        route(
                            'recruitment.offers.update',
                            $candidateOffer
                        ),

                    'submit_for_approval' =>
                        route(
                            'recruitment.offers.submit-for-approval',
                            $candidateOffer
                        ),

                    'approve' =>
                        route(
                            'recruitment.offers.approve',
                            $candidateOffer
                        ),

                    'show' =>
                        route(
                            'recruitment.offers.show',
                            $candidateOffer
                        ),

                    'back' =>
                        route(
                            'recruitment.offers.show',
                            $candidateOffer
                        ),

                    'index' =>
                        route(
                            'recruitment.offers.index'
                        ),
                ],
            ]
        );
    }

    /**
     * Update an offer draft.
     */
    public function update(
        SaveCandidateOfferRequest $request,
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'update',
            $candidateOffer
        );

        $validated = $request->validated();

        $salaryComponents =
            $validated['salary_components'];

        unset(
            $validated['salary_components'],
            $validated['salary_ctc'],
            $validated['salary_in_hand']
        );

        DB::transaction(
            function () use ($candidateOffer, $validated, $salaryComponents) {
                $candidateOffer->update([
                    ...$validated,
                    'pdf_path' => null,
                    'pdf_generated_at' => null,
                ]);

                $this->salaryComponentService
                    ->sync(
                        $candidateOffer,
                        $salaryComponents
                    );

                if ($candidateOffer->pdf_path) {
                    $candidateOffer->forceFill([
                        'pdf_path' => null,
                        'pdf_generated_at' => null,
                    ])->save();
                }
            }
        );

        return redirect()
            ->route(
                'recruitment.offers.show',
                $candidateOffer
            )
            ->with(
                'success',
                'Offer draft updated successfully.'
            );
    }

    /**
     * Move a draft to Pending Approval.
     */
    public function submitForApproval(
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'submitForApproval',
            $candidateOffer
        );

        $this->validateOfferCompleteness(
            $candidateOffer
        );

        $candidateOffer->markPendingApproval();

        return back()->with(
            'success',
            'Offer submitted for approval.'
        );
    }

    /**
     * Approve a pending offer.
     */
    public function approve(
        Request $request,
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'approve',
            $candidateOffer
        );

        if (
            $candidateOffer->status !==
            CandidateOffer::STATUS_PENDING_APPROVAL
        ) {
            return back()->with(
                'error',
                'Only offers pending approval can be approved.'
            );
        }

        $this->validateOfferCompleteness(
            $candidateOffer
        );

        $candidateOffer->markApproved(
            $request->user()->id
        );

        return back()->with(
            'success',
            'Offer approved successfully.'
        );
    }

    /**
     * Create a new draft revision from an existing offer.
     */

    public function createRevisionForm(
        Request $request,
        CandidateOffer $candidateOffer
    ): Response {
        $this->authorize(
            'createRevision',
            $candidateOffer
        );

        $candidateOffer->load([
            'candidate',
            'salaryComponents.percentageOfComponent',
        ]);

        $offerPayload =
            $this->offerFormPayload(
                $candidateOffer
            );

        $offerPayload['id'] = null;

        $offerPayload['status'] =
            CandidateOffer::STATUS_DRAFT;

        $offerPayload['approval_status'] =
            CandidateOffer::STATUS_PENDING_APPROVAL;

        $offerPayload['parent_offer_id'] =
            $candidateOffer->id;

        $offerPayload['version'] =
            $candidateOffer->version + 1;

        $offerPayload['revision_reason'] =
            null;

        return Inertia::render(
            'Recruitment/Offers/Revision',
            [
                'candidate' =>
                    $this->candidatePayload(
                        $candidateOffer->candidate
                    ),

                'offer' =>
                    $offerPayload,

                'options' =>
                    $this->formOptions(),

                'permissions' => [
                    'update' => true,
                ],

                'routes' => [
                    'store' =>
                        route(
                            'recruitment.offers.revisions.store',
                            $candidateOffer
                        ),

                    'back' =>
                        route(
                            'recruitment.offers.show',
                            $candidateOffer
                        ),

                    'index' =>
                        route(
                            'recruitment.offers.index'
                        ),
                ],
            ]
        );
    }

    public function createRevision(
        SaveCandidateOfferRequest $request,
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'createRevision',
            $candidateOffer
        );

        $validated =
            $request->validated();

        $salaryComponents =
            $validated['salary_components'];

        unset(
            $validated['salary_components'],
            $validated['salary_ctc'],
            $validated['salary_in_hand']
        );

        $revision = DB::transaction(
            function () use ($request, $candidateOffer, $validated, $salaryComponents) {
                $lockedOriginalOffer =
                    CandidateOffer::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $candidateOffer->id
                        );

                $latestVersion =
                    CandidateOffer::query()
                        ->where(
                            'candidate_id',
                            $lockedOriginalOffer
                                ->candidate_id
                        )
                        ->max('version');

                $revision =
                    CandidateOffer::create([
                        ...$validated,

                        'candidate_id' =>
                            $lockedOriginalOffer
                                ->candidate_id,

                        'parent_offer_id' =>
                            $lockedOriginalOffer->id,

                        'offer_number' =>
                            $lockedOriginalOffer
                                ->offer_number,

                        'version' =>
                            ((int) $latestVersion) + 1,

                        'status' =>
                            CandidateOffer::STATUS_DRAFT,

                        'approval_status' =>
                            CandidateOffer::STATUS_PENDING_APPROVAL,

                        'generated_by' =>
                            $request->user()->id,

                        'approved_by' => null,

                        'approved_at' => null,

                        'submitted_for_approval_at' =>
                            null,

                        'pdf_path' => null,

                        'pdf_generated_at' =>
                            null,

                        'public_token' =>
                            null,

                        'sent_at' => null,

                        'viewed_at' => null,

                        'accepted_at' => null,

                        'declined_at' => null,

                        'expired_at' => null,

                        'cancelled_at' => null,

                        'salary_ctc' => 0,

                        'salary_in_hand' => 0,
                    ]);

                $this->salaryComponentService
                    ->sync(
                        $revision,
                        $salaryComponents
                    );

                return $revision;
            }
        );

        return redirect()
            ->route(
                'recruitment.offers.show',
                $revision
            )
            ->with(
                'success',
                "Offer revision V{$revision->version} created successfully."
            );
    }

    /**
     * Cancel an open offer.
     */
    public function cancel(
        CancelCandidateOfferRequest $request,
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'cancel',
            $candidateOffer
        );

        if (!$candidateOffer->canBeCancelled()) {
            return back()->with(
                'error',
                'This offer can no longer be cancelled.'
            );
        }

        $candidateOffer->forceFill([
            'status' =>
                CandidateOffer::STATUS_CANCELLED,

            'cancelled_at' => now(),

            'cancellation_reason' =>
                $request->validated('reason'),

            'portal_token_revoked_at' => now(),
        ])->save();

        return redirect()
            ->route(
                'recruitment.offers.show',
                $candidateOffer
            )
            ->with(
                'success',
                'Offer cancelled successfully.'
            );
    }

    /**
     * Soft-delete a draft.
     */
    public function destroy(
        CandidateOffer $candidateOffer
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $candidateOffer
        );

        $candidateOffer->delete();

        return redirect()
            ->route('recruitment.offers.index')
            ->with(
                'success',
                'Offer draft deleted successfully.'
            );
    }

    /**
     * Ensure an offer contains every field required for approval.
     */
    private function validateOfferCompleteness(
        CandidateOffer $offer
    ): void {
        $offer->loadMissing(
            'salaryComponents'
        );

        validator(
            $offer->toArray(),
            [
                'candidate_id' => [
                    'required',
                ],

                'process_name' => [
                    'required',
                    'string',
                ],

                'designation' => [
                    'required',
                    'string',
                ],

                'employment_type' => [
                    'required',
                    'string',
                ],

                'joining_date' => [
                    'required',
                    'date',
                ],

                'offer_valid_till' => [
                    'required',
                    'date',
                ],
            ],
            [
                'process_name.required' =>
                    'The assigned process is missing.',

                'designation.required' =>
                    'The final designation is missing.',

                'joining_date.required' =>
                    'The proposed joining date is missing.',

                'offer_valid_till.required' =>
                    'The offer validity date is missing.',
            ]
        )->validate();

        if (
            $offer->salaryComponents->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'salary_components' =>
                    'At least one salary component is required before submitting the offer.',
            ]);
        }

        if (
            (float) $offer->salary_ctc <= 0
        ) {
            throw ValidationException::withMessages([
                'salary_components' =>
                    'The calculated annual CTC must be greater than zero.',
            ]);
        }

        if (
            (float) $offer->salary_in_hand < 0
        ) {
            throw ValidationException::withMessages([
                'salary_components' =>
                    'The calculated in-hand salary cannot be negative.',
            ]);
        }
    }

    /**
     * Form dropdown options.
     */
    private function formOptions(): array
    {
        return [
            'employment_types' =>
                CandidateOffer::employmentTypeOptions(),

            /*
             * Replace these queries if your project uses
             * different Branch or User columns.
             */
            'branches' =>
                Branch::query()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ])
                    ->map(fn(Branch $branch) => [
                        'value' => $branch->id,
                        'label' => $branch->name,
                    ]),

            'reporting_managers' =>
                User::query()
                    ->orderBy('first_name')
                    ->get([
                        'id',
                        'first_name',
                        'last_name',
                        'employee_id',
                    ])
                    ->map(fn(User $user) => [
                        'value' => $user->id,

                        'label' => trim(
                            "{$user->first_name} {$user->last_name}"
                        ),

                        'employee_id' =>
                            $user->employee_id,
                    ]),

            'salary_component_types' =>
                OfferSalaryComponent::componentTypeOptions(),

            'calculation_types' =>
                OfferSalaryComponent::calculationTypeOptions(),

            'salary_frequencies' =>
                OfferSalaryComponent::frequencyOptions(),
        ];
    }

    /**
     * Initial values for a new offer.
     */
    private function offerDefaults(
        Candidate $candidate
    ): array {
        return [
            'process_name' =>
                $candidate->process_name ?? '',

            'designation' =>
                $candidate->final_designation ?? '',

            'department' =>
                $candidate->department ?? '',

            'branch_id' =>
                $candidate->branch_id ?? null,

            'reporting_manager_id' =>
                null,

            'employment_type' =>
                CandidateOffer::EMPLOYMENT_FULL_TIME,

            'probation_months' => 3,

            'joining_date' => null,

            'reporting_time' => '10:00',

            'work_location' =>
                $candidate->work_location ?? '',

            'salary_ctc' =>
                $candidate->final_ctc_offered
                ?? $candidate->final_salary_offered
                ?? null,

            'salary_in_hand' =>
                $candidate->final_in_hand_offered
                ?? null,

            'salary_basic' => null,
            'salary_hra' => null,
            'salary_special_allowance' => null,
            'salary_pf' => null,
            'salary_bonus' => null,
            'salary_variable' => null,
            'salary_other_allowances' => null,

            'pf_allowed' =>
                (bool) (
                    $candidate->final_pf_allowed
                    ?? false
                ),

            'salary_structure_json' => [],

            'salary_annexure' =>
                $candidate->salary_annexure ?? '',

            'offer_terms' =>
                $this->defaultOfferTerms(),

            'internal_remarks' =>
                $candidate->hiring_notes ?? '',

            'offer_valid_till' =>
                now()->addDays(7)->toDateString(),
        ];
    }

    private function candidatePayload(
        Candidate $candidate
    ): array {
        return [
            'id' => $candidate->id,

            'name' => trim(
                "{$candidate->first_name} {$candidate->last_name}"
            ),

            'first_name' =>
                $candidate->first_name,

            'last_name' =>
                $candidate->last_name,

            'email' =>
                $candidate->email,

            'phone' =>
                $candidate->phone,

            'final_status' =>
                $candidate->final_status,

            'current_status' =>
                $candidate->current_status,

            'process_name' =>
                $candidate->process_name,

            'final_designation' =>
                $candidate->final_designation,

            'final_ctc_offered' =>
                $candidate->final_ctc_offered,

            'final_in_hand_offered' =>
                $candidate->final_in_hand_offered,

            'final_pf_allowed' =>
                (bool) $candidate->final_pf_allowed,
        ];
    }

    private function offerListPayload(
        CandidateOffer $offer
    ): array {
        return [
            'id' => $offer->id,

            'offer_number' =>
                $offer->offer_number,

            'version' =>
                $offer->version,

            'status' =>
                $offer->status,

            'status_label' =>
                $offer->status_label,

            'candidate' => [
                'id' =>
                    $offer->candidate->id,

                'name' => trim(
                    "{$offer->candidate->first_name} {$offer->candidate->last_name}"
                ),

                'email' =>
                    $offer->candidate->email,

                'phone' =>
                    $offer->candidate->phone,
            ],

            'designation' =>
                $offer->designation,

            'process_name' =>
                $offer->process_name,

            'salary_ctc' =>
                $offer->salary_ctc,

            'salary_in_hand' =>
                $offer->salary_in_hand,

            'joining_date' =>
                $offer->joining_date
                        ?->format('Y-m-d'),

            'offer_valid_till' =>
                $offer->offer_valid_till
                        ?->format('Y-m-d'),

            'created_at' =>
                $offer->created_at
                        ?->format('d M Y, h:i A'),

            'generated_by' =>
                $offer->generatedBy
                ? trim(
                    "{$offer->generatedBy->first_name} {$offer->generatedBy->last_name}"
                )
                : null,
        ];
    }

    private function offerFormPayload(
        CandidateOffer $offer
    ): array {
        return [
            'id' => $offer->id,

            'offer_number' =>
                $offer->offer_number,

            'version' =>
                $offer->version,

            'status' =>
                $offer->status,

            'process_name' =>
                $offer->process_name,

            'designation' =>
                $offer->designation,

            'department' =>
                $offer->department,

            'branch_id' =>
                $offer->branch_id,

            'reporting_manager_id' =>
                $offer->reporting_manager_id,

            'employment_type' =>
                $offer->employment_type,

            'probation_months' =>
                $offer->probation_months,

            'joining_date' =>
                $offer->joining_date
                        ?->format('Y-m-d'),

            'reporting_time' =>
                $offer->reporting_time
                ? substr(
                    $offer->reporting_time,
                    0,
                    5
                )
                : null,

            'work_location' =>
                $offer->work_location,

            'salary_ctc' =>
                $offer->salary_ctc,

            'salary_in_hand' =>
                $offer->salary_in_hand,

            'salary_basic' =>
                $offer->salary_basic,

            'salary_hra' =>
                $offer->salary_hra,

            'salary_special_allowance' =>
                $offer->salary_special_allowance,

            'salary_pf' =>
                $offer->salary_pf,

            'salary_bonus' =>
                $offer->salary_bonus,

            'salary_variable' =>
                $offer->salary_variable,

            'salary_other_allowances' =>
                $offer->salary_other_allowances,

            'pf_allowed' =>
                $offer->pf_allowed,

            'salary_components' =>
                $this->salaryComponentPayload(
                    $offer
                ),

            'salary_annexure' =>
                $offer->salary_annexure,

            'offer_terms' =>
                $offer->offer_terms,

            'internal_remarks' =>
                $offer->internal_remarks,

            'offer_valid_till' =>
                $offer->offer_valid_till
                        ?->format('Y-m-d'),
        ];
    }

    private function offerDetailPayload(
        CandidateOffer $offer
    ): array {
        return [
            ...$this->offerFormPayload($offer),

            'status_label' =>
                $offer->status_label,

            'employment_type_label' =>
                $offer->employment_type_label,

            'public_token' =>
                $offer->public_token,

            'pdf_path' =>
                $offer->pdf_path,

            'pdf_generated_at' =>
                $offer->pdf_generated_at
                        ?->format('d M Y, h:i A'),

            'approved_at' =>
                $offer->approved_at
                        ?->format('d M Y, h:i A'),

            'sent_at' =>
                $offer->sent_at
                        ?->format('d M Y, h:i A'),

            'viewed_at' =>
                $offer->viewed_at
                        ?->format('d M Y, h:i A'),

            'accepted_at' =>
                $offer->accepted_at
                        ?->format('d M Y, h:i A'),

            'declined_at' =>
                $offer->declined_at
                        ?->format('d M Y, h:i A'),

            'expired_at' =>
                $offer->expired_at
                        ?->format('d M Y, h:i A'),

            'cancelled_at' =>
                $offer->cancelled_at
                        ?->format('d M Y, h:i A'),

            'decline_reason' =>
                $offer->decline_reason,

            'candidate' =>
                $this->candidatePayload(
                    $offer->candidate
                ),

            'branch' =>
                $offer->branch
                ? [
                    'id' =>
                        $offer->branch->id,

                    'name' =>
                        $offer->branch->name,
                ]
                : null,

            'reporting_manager' =>
                $offer->reportingManager
                ? [
                    'id' =>
                        $offer->reportingManager->id,

                    'name' => trim(
                        "{$offer->reportingManager->first_name} {$offer->reportingManager->last_name}"
                    ),
                ]
                : null,

            'generated_by' =>
                $offer->generatedBy
                ? trim(
                    "{$offer->generatedBy->first_name} {$offer->generatedBy->last_name}"
                )
                : null,

            'approved_by' =>
                $offer->approvedBy
                ? trim(
                    "{$offer->approvedBy->first_name} {$offer->approvedBy->last_name}"
                )
                : null,

            'created_at' =>
                $offer->created_at
                        ?->format('d M Y, h:i A'),

            'updated_at' =>
                $offer->updated_at
                        ?->format('d M Y, h:i A'),
        ];
    }

    private function defaultOfferTerms(): string
    {
        return <<<'HTML'
            We are pleased to offer you employment with our organization, subject to the terms and conditions stated in this offer.

            Your appointment will be subject to successful verification of the information and documents submitted by you.

            You are required to report on the proposed joining date with all requested original documents and identification proofs.

            The organization reserves the right to withdraw this offer if any submitted information is found to be incorrect or misleading.
            HTML;
    }

    private function defaultSalaryComponents(): array
    {
        return collect(
            OfferSalaryComponent::defaultComponents()
        )
            ->values()
            ->map(
                function (array $component, int $index) {
                    return [
                        ...$component,

                        'id' => null,

                        'row_key' =>
                            'component-' .
                            ($index + 1),

                        /*
                         * Connect percentage rows to Basic Salary
                         * through the frontend row key.
                         */
                        'percentage_of_row_key' =>
                            $component[
                                'calculation_type'
                            ] ===
                            OfferSalaryComponent::CALCULATION_PERCENTAGE
                            ? 'component-1'
                            : null,

                        'monthly_amount' =>
                            0,

                        'annual_amount' =>
                            0,
                    ];
                }
            )
            ->all();
    }

    private function salaryComponentPayload(
        CandidateOffer $offer
    ): array {
        $offer->loadMissing([
            'salaryComponents.percentageOfComponent',
        ]);

        $idToRowKey =
            $offer->salaryComponents
                ->values()
                ->mapWithKeys(
                    fn(
                    OfferSalaryComponent $component,
                    int $index
                ) => [
                        $component->id =>
                            'component-' .
                            ($index + 1),
                    ]
                );

        return $offer->salaryComponents
            ->values()
            ->map(
                function (OfferSalaryComponent $component, int $index) use ($idToRowKey) {
                    return [
                        'id' =>
                            $component->id,

                        'row_key' =>
                            $idToRowKey[
                                $component->id
                            ],

                        'component_name' =>
                            $component->component_name,

                        'component_type' =>
                            $component->component_type,

                        'calculation_type' =>
                            $component->calculation_type,

                        'percentage_of_component_id' =>
                            $component
                                ->percentage_of_component_id,

                        'percentage_of_row_key' =>
                            $component
                                ->percentage_of_component_id
                            ? (
                                $idToRowKey[
                                    $component
                                        ->percentage_of_component_id
                                ]
                                ?? null
                            )
                            : null,

                        'amount' =>
                            (float) $component->amount,

                        'percentage' =>
                            $component->percentage !== null
                            ? (float) $component->percentage
                            : null,

                        'frequency' =>
                            $component->frequency,

                        'show_in_offer' =>
                            $component->show_in_offer,

                        'affects_in_hand' =>
                            $component->affects_in_hand,

                        'is_taxable' =>
                            $component->is_taxable,

                        'sort_order' =>
                            $component->sort_order,

                        'description' =>
                            $component->description,

                        'monthly_amount' =>
                            $component->monthly_amount,

                        'annual_amount' =>
                            $component->annual_amount,
                    ];
                }
            )
            ->all();
    }

    private function applyIndexFilters(
        Builder $query,
        array $filters
    ): void {
        $query
            ->when(
                filled(
                    $filters['search']
                    ?? null
                ),
                function (Builder $query) use ($filters) {
                    $search =
                        trim(
                            $filters['search']
                        );

                    $query->where(
                        function (Builder $query) use ($search) {
                            $query
                                ->where(
                                    'offer_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'designation',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'department',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'candidate',
                                    function (Builder $candidateQuery) use ($search) {
                                        $candidateQuery
                                            ->where(
                                                'first_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'last_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'phone',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                filled(
                    $filters['status']
                    ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                filled(
                    $filters[
                        'approval_status'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'approval_status',
                    $filters[
                        'approval_status'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'department'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'department',
                    $filters[
                        'department'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'designation'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'designation',
                    $filters[
                        'designation'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'employment_type'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'employment_type',
                    $filters[
                        'employment_type'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'generated_by'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'generated_by',
                    $filters[
                        'generated_by'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'created_from'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->whereDate(
                    'created_at',
                    '>=',
                    $filters[
                        'created_from'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'created_to'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->whereDate(
                    'created_at',
                    '<=',
                    $filters[
                        'created_to'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'valid_from'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->whereDate(
                    'offer_valid_till',
                    '>=',
                    $filters[
                        'valid_from'
                    ]
                )
            )
            ->when(
                filled(
                    $filters[
                        'valid_to'
                    ] ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->whereDate(
                    'offer_valid_till',
                    '<=',
                    $filters[
                        'valid_to'
                    ]
                )
            )
            ->when(
                filled(
                    $filters['version']
                    ?? null
                ),
                fn(
                Builder $query
            ) =>
                $query->where(
                    'version',
                    $filters[
                        'version'
                    ]
                )
            );
    }

    private function offerIndexPayload(
        Request $request,
        CandidateOffer $offer
    ): array {
        return [
            'id' =>
                $offer->id,

            'offer_number' =>
                $offer->offer_number,

            'version' =>
                $offer->version,

            'status' =>
                $offer->status,

            'approval_status' =>
                $offer->approval_status,

            'designation' =>
                $offer->designation,

            'department' =>
                $offer->department,

            'work_location' =>
                $offer->work_location,

            'employment_type' =>
                $offer->employment_type,

            'joining_date' =>
                optional(
                    $offer->joining_date
                )?->format('Y-m-d'),

            'offer_valid_till' =>
                optional(
                    $offer->offer_valid_till
                )?->format('Y-m-d'),

            'salary_ctc' =>
                (float) $offer->salary_ctc,

            'salary_in_hand' =>
                (float) $offer->salary_in_hand,

            'currency' =>
                $offer->currency ?? 'INR',

            'created_at' =>
                optional(
                    $offer->created_at
                )?->toISOString(),

            'is_latest_version' =>
                !$offer
                    ->newQuery()
                    ->where(
                        'candidate_id',
                        $offer->candidate_id
                    )
                    ->where(
                        'offer_number',
                        $offer->offer_number
                    )
                    ->where(
                        'version',
                        '>',
                        $offer->version
                    )
                    ->exists(),

            'candidate' => [
                'id' =>
                    $offer->candidate?->id,

                'name' =>
                    $offer->candidate?->name,

                'first_name' =>
                    $offer->candidate?->first_name,

                'middle_name' =>
                    $offer->candidate?->middle_name,

                'last_name' =>
                    $offer->candidate?->last_name,

                'email' =>
                    $offer->candidate?->email,

                'phone' =>
                    $offer->candidate?->phone,

                'photo_url' =>
                    $offer->candidate?->photo_url,
            ],

            'generated_by' =>
                $offer->generatedBy
                ? [
                    'id' =>
                        $offer
                            ->generatedBy
                            ->id,

                    'name' =>
                        $offer
                            ->generatedBy
                            ->name,
                ]
                : null,

            'permissions' => [
                'update' =>
                    $request->user()->can(
                        'update',
                        $offer
                    ),

                'create_revision' =>
                    $request->user()->can(
                        'createRevision',
                        $offer
                    ),

                'generate_pdf' =>
                    $request->user()->can(
                        'generatePdf',
                        $offer
                    ),

                'send' =>
                    $request->user()->can(
                        'send',
                        $offer
                    ),

                'delete' =>
                    $request->user()->can(
                        'delete',
                        $offer
                    ),
            ],

            'routes' => [
                'show' =>
                    route(
                        'recruitment.offers.show',
                        $offer
                    ),

                'edit' =>
                    route(
                        'recruitment.offers.edit',
                        $offer
                    ),

                'revision' =>
                    route(
                        'recruitment.offers.revisions.create',
                        $offer
                    ),

                'generate_pdf' =>
                    route(
                        'recruitment.offers.generate-pdf',
                        $offer
                    ),

                'send' =>
                    route(
                        'recruitment.offers.send.create',
                        $offer
                    ),

                'destroy' =>
                    route(
                        'recruitment.offers.destroy',
                        $offer
                    ),
            ],
        ];
    }

    private function offerStatistics(): array
    {
        $statistics =
            CandidateOffer::query()
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->selectRaw(
                    "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft",
                    [
                        CandidateOffer::STATUS_DRAFT,
                    ]
                )
                ->selectRaw(
                    "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_approval",
                    [
                        CandidateOffer::STATUS_PENDING_APPROVAL,
                    ]
                )
                ->selectRaw(
                    "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved",
                    [
                        CandidateOffer::STATUS_APPROVED,
                    ]
                )
                ->selectRaw(
                    "SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as sent",
                    [
                        CandidateOffer::STATUS_SENT,
                        CandidateOffer::STATUS_VIEWED,
                    ]
                )
                ->selectRaw(
                    "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as accepted",
                    [
                        CandidateOffer::STATUS_ACCEPTED,
                    ]
                )
                ->selectRaw(
                    "SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as declined",
                    [
                        CandidateOffer::STATUS_DECLINED,
                    ]
                )
                ->first();

        return [
            'total' =>
                (int) (
                    $statistics->total
                    ?? 0
                ),

            'draft' =>
                (int) (
                    $statistics->draft
                    ?? 0
                ),

            'pending_approval' =>
                (int) (
                    $statistics
                        ->pending_approval
                    ?? 0
                ),

            'approved' =>
                (int) (
                    $statistics->approved
                    ?? 0
                ),

            'sent' =>
                (int) (
                    $statistics->sent
                    ?? 0
                ),

            'accepted' =>
                (int) (
                    $statistics->accepted
                    ?? 0
                ),

            'declined' =>
                (int) (
                    $statistics->declined
                    ?? 0
                ),
        ];
    }

    private function offerShowPayload(
        CandidateOffer $offer,
        array $salaryCalculation
    ): array {
        $calculatedComponents = collect($salaryCalculation)
            ->keyBy(fn(array $component) => $component['id']);

        return [
            'id' =>
                $offer->id,

            'offer_number' =>
                $offer->offer_number,

            'version' =>
                $offer->version,

            'status' =>
                $offer->status,

            'approval_status' =>
                $offer->approval_status,

            'process' =>
                $offer->process,

            'designation' => $offer->designation,

            'department' => $offer->department,

            'branch' =>
                $offer->branch?->name,

            'reporting_manager' =>
                $offer->reportingManager?->name
                ?? $offer->reporting_manager,

            'employment_type' =>
                $offer->employment_type,

            'work_location' =>
                $offer->work_location,

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

            'currency' =>
                $offer->currency ?? 'INR',

            'pf_allowed' =>
                (bool) $offer->pf_allowed,

            'salary_ctc' =>
                (float) $offer->salary_ctc,

            'salary_in_hand' =>
                (float) $offer->salary_in_hand,

            'offer_terms' =>
                $offer->offer_terms,

            'salary_annexure' =>
                $offer->salary_annexure,

            'internal_notes' =>
                $offer->internal_notes,

            'remarks' =>
                $offer->remarks,

            'revision_reason' =>
                $offer->revision_reason,

            'valid_from' =>
                optional(
                    $offer->valid_from
                )?->format('Y-m-d'),

            'offer_valid_till' =>
                optional(
                    $offer->offer_valid_till
                )?->format('Y-m-d'),

            'created_at' =>
                optional(
                    $offer->created_at
                )?->toISOString(),

            'submitted_for_approval_at' =>
                optional(
                    $offer
                        ->submitted_for_approval_at
                )?->toISOString(),

            'approved_at' =>
                optional(
                    $offer->approved_at
                )?->toISOString(),

            'rejected_at' =>
                optional(
                    $offer->rejected_at
                )?->toISOString(),

            'sent_at' =>
                optional(
                    $offer->sent_at
                )?->toISOString(),

            'viewed_at' =>
                optional(
                    $offer->viewed_at
                )?->toISOString(),

            'accepted_at' =>
                optional(
                    $offer->accepted_at
                )?->toISOString(),

            'declined_at' =>
                optional(
                    $offer->declined_at
                )?->toISOString(),

            'expired_at' =>
                optional(
                    $offer->expired_at
                )?->toISOString(),

            'cancelled_at' =>
                optional(
                    $offer->cancelled_at
                )?->toISOString(),

            'rejection_reason' =>
                $offer->rejection_reason,

            'decline_reason' =>
                $offer->decline_reason,

            'cancellation_reason' =>
                $offer->cancellation_reason,

            'pdf_path' =>
                $offer->pdf_path,

            'pdf_generated_at' =>
                optional(
                    $offer->pdf_generated_at
                )?->toISOString(),

            'generated_by' =>
                $offer->generatedBy
                ? [
                    'id' =>
                        $offer->generatedBy->id,

                    'name' =>
                        $offer->generatedBy->name,
                ]
                : null,

            'approved_by' =>
                $offer->approvedBy
                ? [
                    'id' =>
                        $offer->approvedBy->id,

                    'name' =>
                        $offer->approvedBy->name,
                ]
                : null,

            'salary_components' =>
                $offer
                    ->salaryComponents
                    ->map(
                        function (OfferSalaryComponent $component) use ($calculatedComponents) {
                            $calculated =
                                $calculatedComponents->get(
                                    $component->id,
                                    []
                                );

                            return [
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
                                    (float) $component->amount,

                                'percentage' =>
                                    $component->percentage !== null
                                    ? (float) $component->percentage
                                    : null,

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

                                'percentage_of_component' =>
                                    $component
                                        ->percentageOfComponent
                                    ? [
                                        'id' =>
                                            $component
                                                ->percentageOfComponent
                                                ->id,

                                        'component_name' =>
                                            $component
                                                ->percentageOfComponent
                                                ->component_name,
                                    ]
                                    : null,
                            ];
                        }
                    )
                    ->values()
                    ->all(),
        ];
    }

    private function offerVersionsPayload(
        CandidateOffer $offer
    ): array {
        $rootOfferId =
            $offer->parent_offer_id
            ?? $offer->id;

        return CandidateOffer::query()
            ->with('generatedBy')
            ->where(
                function (Builder $query) use ($offer, $rootOfferId) {
                    $query
                        ->where(
                            'id',
                            $rootOfferId
                        )
                        ->orWhere(
                            'parent_offer_id',
                            $rootOfferId
                        )
                        ->orWhere(
                            'offer_number',
                            $offer->offer_number
                        );
                }
            )
            ->orderByDesc('version')
            ->get()
            ->map(
                fn(
                CandidateOffer $version
            ) => [
                    'id' =>
                        $version->id,

                    'version' =>
                        $version->version,

                    'status' =>
                        $version->status,

                    'revision_reason' =>
                        $version->revision_reason,

                    'created_at' =>
                        optional(
                            $version->created_at
                        )?->toISOString(),

                    'is_latest' =>
                        $version->version ===
                        CandidateOffer::query()
                            ->where(
                                'offer_number',
                                $offer->offer_number
                            )
                            ->max('version'),

                    'generated_by' =>
                        $version->generatedBy
                        ? [
                            'id' =>
                                $version
                                    ->generatedBy
                                    ->id,

                            'name' =>
                                $version
                                    ->generatedBy
                                    ->name,
                        ]
                        : null,

                    'routes' => [
                        'show' =>
                            route(
                                'recruitment.offers.show',
                                $version
                            ),
                    ],
                ]
            )
            ->values()
            ->all();
    }

    public function generatePdf(
        CandidateOffer $candidateOffer,
        OfferPdfService $offerPdfService
    ): RedirectResponse {
        $this->authorize(
            'generatePdf',
            $candidateOffer
        );

        try {
            $offerPdfService->generate(
                $candidateOffer
            );

            return back()->with(
                'success',
                'Offer letter PDF generated successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                app()->isLocal()
                ? $exception->getMessage()
                : 'Unable to generate the offer letter PDF.'
            );
        }
    }

    public function downloadPdf(
        CandidateOffer $candidateOffer
    ): StreamedResponse|RedirectResponse {
        $this->authorize(
            'view',
            $candidateOffer
        );

        if (
            !$candidateOffer->pdf_path ||
            !Storage::disk('public')->exists(
                $candidateOffer->pdf_path
            )
        ) {
            return back()->with(
                'error',
                'Generate the offer letter PDF before downloading it.'
            );
        }

        $filename = sprintf(
            '%s-v%s.pdf',
            $candidateOffer->offer_number
            ?: 'offer-letter',
            $candidateOffer->version ?: 1
        );

        return Storage::disk('public')->download(
            $candidateOffer->pdf_path,
            $filename,
            [
                'Content-Type' =>
                    'application/pdf',
            ]
        );
    }

    public function createSend(
        CandidateOffer $candidateOffer
    ): Response|RedirectResponse {
        $this->authorize(
            'send',
            $candidateOffer
        );

        $candidateOffer->loadMissing([
            'candidate',
        ]);

        if (!$candidateOffer->canBeSent()) {
            return redirect()
                ->route(
                    'recruitment.offers.show',
                    $candidateOffer
                )
                ->with(
                    'error',
                    'Only an approved or previously sent offer can be sent.'
                );
        }

        return Inertia::render(
            'Recruitment/Offers/Send',
            [
                'offer' => [
                    'id' =>
                        $candidateOffer->id,

                    'offer_number' =>
                        $candidateOffer->offer_number,

                    'version' =>
                        $candidateOffer->version,

                    'status' =>
                        $candidateOffer->status,

                    'designation' =>
                        $candidateOffer
                            ->designation
                        ?? $candidateOffer
                            ->getRawOriginal(
                                'designation'
                            ),

                    'department' =>
                        $candidateOffer
                            ->department
                        ?? $candidateOffer
                            ->getRawOriginal(
                                'department'
                            ),

                    'joining_date' =>
                        optional(
                            $candidateOffer->joining_date
                        )?->format('Y-m-d'),

                    'valid_till' =>
                        optional(
                            $candidateOffer->valid_till
                        )?->format('Y-m-d'),

                    'pdf_available' =>
                        filled(
                            $candidateOffer->pdf_path
                        ),

                    'send_count' =>
                        $candidateOffer->send_count,
                ],

                'candidate' => [
                    'id' =>
                        $candidateOffer->candidate->id,

                    'name' =>
                        $candidateOffer
                            ->candidate
                            ->full_name
                        ?? trim(
                            (
                                $candidateOffer
                                    ->candidate
                                    ->first_name
                                ?? ''
                            )
                            . ' ' .
                            (
                                $candidateOffer
                                    ->candidate
                                    ->last_name
                                ?? ''
                            )
                        ),

                    'email' =>
                        $candidateOffer
                            ->candidate
                            ->email,
                ],

                'defaults' => [
                    'recipient_email' =>
                        $candidateOffer
                            ->candidate
                            ->email,

                    'cc' => [],

                    'subject' => sprintf(
                        'Employment Offer – %s',
                        $candidateOffer
                            ->designation
                        ?? 'Position'
                    ),

                    'message' => sprintf(
                        "We are pleased to offer you the position of %s.\n\nPlease review the attached offer letter carefully. The offer remains valid until %s.\n\nWe look forward to welcoming you to our organization.",
                        $candidateOffer
                            ->designation
                        ?? 'the discussed position',

                        optional(
                            $candidateOffer->valid_till
                        )?->format('d M Y')
                        ?? 'the communicated validity date'
                    ),

                    'attach_pdf' => true,
                ],

                'routes' => [
                    'show' => route(
                        'recruitment.offers.show',
                        $candidateOffer
                    ),

                    'send' => route(
                        'recruitment.offers.send',
                        $candidateOffer
                    ),

                    'generate_pdf' => route(
                        'recruitment.offers.generate-pdf',
                        $candidateOffer
                    ),
                ],
            ]
        );
    }

    public function send(
        SendCandidateOfferRequest $request,
        CandidateOffer $candidateOffer,
        OfferPdfService $offerPdfService,
        OfferPortalTokenService $portalTokenService
    ): RedirectResponse {
        $this->authorize(
            'send',
            $candidateOffer
        );

        if (!$candidateOffer->canBeSent()) {
            return back()->with(
                'error',
                'This offer cannot currently be sent.'
            );
        }

        $validated = $request->validated();

        try {
            if (
                $validated['attach_pdf'] &&
                (
                    !$candidateOffer->pdf_path ||
                    !Storage::disk('public')->exists(
                        $candidateOffer->pdf_path
                    )
                )
            ) {
                $offerPdfService->generate(
                    $candidateOffer
                );

                $candidateOffer->refresh();
            }

            $candidateOffer->loadMissing([
                'candidate',
            ]);

            $mail = Mail::to(
                $validated['recipient_email']
            );

            if (!empty($validated['cc'])) {
                $mail->cc(
                    $validated['cc']
                );
            }

            $portalToken =
                $portalTokenService->rotate(
                    $candidateOffer
                );

            $portalUrl = route(
                'candidate-offers.portal.show',
                $portalToken
            );

            $mail->send(
                new CandidateOfferMail(
                    offer: $candidateOffer,
                    emailSubject:
                        $validated['subject'],
                    emailMessage:
                        $validated['message'],
                    attachPdf:
                        $validated['attach_pdf'],
                    portalUrl:
                        $portalUrl,
                )
            );

            DB::transaction(
                function () use ($candidateOffer, $validated, $request): void {
                    $candidateOffer->forceFill([
                        'status' =>
                            CandidateOffer::STATUS_SENT,

                        'sent_at' => now(),

                        'sent_to_email' =>
                            $validated[
                                'recipient_email'
                            ],

                        'email_subject' =>
                            $validated['subject'],

                        'email_message' =>
                            $validated['message'],

                        'email_cc' =>
                            $validated['cc'] ?? [],

                        'sent_by' =>
                            $request->user()->id,

                        'send_count' =>
                            $candidateOffer
                                ->send_count + 1,
                    ])->save();
                }
            );

            return redirect()
                ->route(
                    'recruitment.offers.show',
                    $candidateOffer
                )
                ->with(
                    'success',
                    'Offer letter sent successfully.'
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to send the offer letter.'
                );
        }
    }

}