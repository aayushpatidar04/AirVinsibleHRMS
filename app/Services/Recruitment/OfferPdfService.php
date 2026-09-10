<?php

namespace App\Services\Recruitment;

use App\Models\CandidateOffer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OfferPdfService
{
    public function generate(CandidateOffer $offer): string
    {
        $offer->loadMissing([
            'candidate',
            'branch',
            'reportingManager',
            'salaryComponents' => fn($query) =>
                $query->orderBy('sort_order'),
            'generatedBy',
            'approvedBy',
        ]);

        if (!$offer->candidate) {
            throw new RuntimeException(
                'Candidate information is missing for this offer.'
            );
        }

        $salarySummary = $this->salarySummary($offer);

        $pdf = Pdf::loadView(
            'pdf.recruitment.offer-letter',
            [
                'offer' => $offer,
                'candidate' => $offer->candidate,
                'salarySummary' => $salarySummary,
                'company' => $this->companyData(),
            ]
        );

        $pdf
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ]);

        $directory = sprintf(
            'recruitment/offers/%s',
            $offer->candidate_id
        );

        Storage::disk('public')->makeDirectory(
            $directory
        );

        if (
            $offer->pdf_path &&
            Storage::disk('public')->exists(
                $offer->pdf_path
            )
        ) {
            Storage::disk('public')->delete(
                $offer->pdf_path
            );
        }

        $filename = sprintf(
            '%s-v%s.pdf',
            Str::slug(
                $offer->offer_number
                ?: 'offer-' . $offer->id
            ),
            $offer->version ?: 1
        );

        $path = $directory . '/' . $filename;

        Storage::disk('public')->put(
            $path,
            $pdf->output()
        );

        $offer->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();

        return $path;
    }

    private function salarySummary(
        CandidateOffer $offer
    ): array {
        $earnings = $offer->salaryComponents
            ->where('component_type', 'earning');

        $deductions = $offer->salaryComponents
            ->where('component_type', 'deduction');

        $employerContributions =
            $offer->salaryComponents->where(
                'component_type',
                'employer_contribution'
            );

        return [
            'monthly_gross' => $earnings->sum(
                fn($component) =>
                (float) (
                    $component->monthly_amount
                    ?? $component->calculated_monthly_amount
                    ?? 0
                )
            ),

            'monthly_deductions' =>
                $deductions->sum(
                    fn($component) =>
                    (float) (
                        $component->monthly_amount
                        ?? $component->calculated_monthly_amount
                        ?? 0
                    )
                ),

            'monthly_employer_contributions' =>
                $employerContributions->sum(
                    fn($component) =>
                    (float) (
                        $component->monthly_amount
                        ?? $component->calculated_monthly_amount
                        ?? 0
                    )
                ),

            'monthly_in_hand' =>
                (float) $offer->salary_in_hand,

            'annual_ctc' =>
                (float) $offer->salary_ctc,
        ];
    }

    private function companyData(): array
    {
        return [
            'name' => config(
                'company.name',
                config('app.name')
            ),

            'legal_name' => config(
                'company.legal_name',
                config('company.name', config('app.name'))
            ),

            'logo_path' => $this->logoPath(),

            'address' => config(
                'company.address'
            ),

            'email' => config(
                'company.email'
            ),

            'phone' => config(
                'company.phone'
            ),

            'website' => config(
                'company.website'
            ),

            'hr_name' => config(
                'company.hr_name',
                'Human Resources'
            ),

            'hr_designation' => config(
                'company.hr_designation',
                'HR Department'
            ),

            'signature_path' =>
                $this->signaturePath(),
        ];
    }

    private function logoPath(): ?string
    {
        $relativePath = config(
            'company.logo_path',
            'images/company-logo.png'
        );

        $path = public_path($relativePath);

        return is_file($path)
            ? $path
            : null;
    }

    private function signaturePath(): ?string
    {
        $relativePath = config(
            'company.hr_signature_path',
            'images/hr-signature.png'
        );

        $path = public_path($relativePath);

        return is_file($path)
            ? $path
            : null;
    }
}