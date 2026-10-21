<?php

namespace App\Services\Recruitment;

use App\Models\CandidateIntentLetter;
use App\Support\LetterHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CandidateIntentLetterPdfService
{
    public function generate(CandidateIntentLetter $letter): string
    {
        $letter->loadMissing('candidate');

        if (!$letter->candidate) {
            throw new RuntimeException('Candidate information is missing for this Letter of Intent.');
        }

        $path = $this->render($letter, false);

        $letter->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    public function generateAcknowledged(CandidateIntentLetter $letter): string
    {
        $letter->loadMissing('candidate');

        if (!$letter->isAcknowledged()) {
            throw new RuntimeException('The Letter of Intent has not been acknowledged.');
        }

        $path = $this->render($letter, true);

        $letter->forceFill([
            'acknowledged_pdf_path' => $path,
        ])->save();

        return $path;
    }

    private function render(
        CandidateIntentLetter $letter,
        bool $includeAcknowledgement
    ): string {
        $pdf = Pdf::loadView('pdf.recruitment.intent-letter', [
            'letter' => $letter,
            'candidate' => $letter->candidate,
            'company' => [
                'name' => config('company.name', config('app.name')),
                'legal_name' => config('company.legal_name', 'Airvincible Pvt. Ltd.'),
                'logo_path' => LetterHelper::logoPath(),
                'footer' => LetterHelper::footer(),
                'hr_name' => config('company.hr_name'),
                'hr_designation' => config('company.hr_designation'),
                'signature_path' => LetterHelper::signaturePath(),
            ],
            'includeAcknowledgement' => $includeAcknowledgement,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ]);

        $directory = sprintf(
            'recruitment/intent-letters/%s',
            $letter->candidate_id
        );

        Storage::disk('local')->makeDirectory($directory);

        $filename = sprintf(
            '%s%s.pdf',
            $letter->letter_number,
            $includeAcknowledgement ? '-acknowledged' : ''
        );
        $path = $directory . '/' . $filename;

        if (!Storage::disk('local')->put($path, $pdf->output())) {
            throw new RuntimeException('Unable to save the Letter of Intent PDF.');
        }

        return $path;
    }

}
