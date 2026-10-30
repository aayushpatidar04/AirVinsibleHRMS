<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\DocumentRequirement;
use App\Models\DocumentUploadOtp;
use App\Services\DocumentUploadOtpService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CandidateDocumentController extends Controller
{
    /**
     * Public page: candidate searches by email and uploads documents.
     */
    public function showUploadForm(): InertiaResponse
    {
        return Inertia::render('Candidate/DocumentUpload');
    }

    /**
     * Handle email lookup.
     */
    public function lookup(Request $request, DocumentUploadOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $candidate = Candidate::where('email', $data['email'])->first();

        if (!$candidate) {
            // Don't reveal whether email exists - still send OTP
            $otpService->sendOtp($data['email'], $request->ip());
            return back()->with('success', 'If an account exists for this email, a verification code has been sent.');
        }

        $otpService->sendOtp($data['email'], $request->ip());

        return redirect()->route('candidate.documents.verify-otp', [
            'email' => $data['email'],
        ]);
    }

    /**
     * Show candidate document upload page.
     */
    public function verifyOtpForm(string $email): InertiaResponse
    {
        return Inertia::render('Candidate/VerifyOtp', [
            'email' => $email,
        ]);
    }

    public function verifyOtp(Request $request, DocumentUploadOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if (!$otpService->verifyOtp($data['email'], $data['otp'])) {
            return back()->withErrors([
                'otp' => 'Invalid or expired verification code.',
            ])->onlyInput('email');
        }

        $candidate = Candidate::where('email', $data['email'])->first();

        if (!$candidate) {
            return redirect()->route('candidate.documents.upload-form')
                ->with('error', 'No candidate found with this email.');
        }

        return redirect()->route('candidate.documents.upload', ['candidate' => $candidate->id]);
    }

    public function resendOtp(Request $request, DocumentUploadOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $otpService->sendOtp($data['email'], $request->ip());

        return back()->with('success', 'A new verification code has been sent.');
    }

    public function upload(Request $request, Candidate $candidate): InertiaResponse|RedirectResponse
    {
        // Verify OTP was completed for this email
        $otpService = app(DocumentUploadOtpService::class);
        if (!$otpService->isVerified($candidate->email)) {
            return redirect()->route('candidate.documents.verify-otp', [
                'email' => $candidate->email,
            ])->with('error', 'Please verify your email first.');
        }

        $requirements = DocumentRequirement::active()
            ->ordered()
            ->get()
            ->map(function ($req) use ($candidate) {
                $existing = $candidate->documents()
                    ->where('document_requirement_id', $req->id)
                    ->first();

                $req->existing = $existing;
                $req->is_submitted = $existing?->file_path ? true : false;
                $req->is_verified = $existing?->is_verified ?? false;

                return $req;
            });

        $missingRequired = $candidate->missingRequiredDocuments();

        return Inertia::render('Candidate/DocumentUpload', [
            'candidate' => $candidate,
            'requirements' => $requirements,
            'missingRequired' => $missingRequired,
        ]);
    }

    /**
     * Upload a document.
     */
    public function store(Request $request, Candidate $candidate): RedirectResponse
    {
        $data = $request->validate([
            'document_requirement_id' => 'required|exists:document_requirements,id',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $requirement = DocumentRequirement::findOrFail($data['document_requirement_id']);

        /** @var UploadedFile $file */
        $file = $data['document'];
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = "candidate_documents/{$candidate->id}/" . $filename;

        Storage::disk('public')->putFileAs(
            "candidate_documents/{$candidate->id}",
            $file,
            $filename
        );

        $existing = $candidate->documents()
            ->where('document_requirement_id', $requirement->id)
            ->first();

        if ($existing) {
            // Delete old file
            if (Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $existing->update([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
                'verification_notes' => null,
            ]);
        } else {
            CandidateDocument::create([
                'candidate_id' => $candidate->id,
                'document_requirement_id' => $requirement->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Delete a document.
     */
    public function destroy(Candidate $candidate, CandidateDocument $document): RedirectResponse
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    /**
     * Admin: verify a document.
     */
    public function verify(Request $request, CandidateDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'verification_notes' => 'nullable|string|max:500',
        ]);

        $document->markVerified(auth()->id(), $data['verification_notes'] ?? null);

        return back()->with('success', 'Document verified.');
    }
}
