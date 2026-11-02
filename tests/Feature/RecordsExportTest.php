<?php

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\DocumentRequirement;
use App\Models\User;
use App\Services\RecordsExportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function recordsExportBranch(string $name, string $code): Branch
{
    return Branch::query()->create([
        'name' => $name,
        'code' => $code,
        'address' => 'Export test address',
        'city' => $name,
        'state' => 'Test',
        'postal_code' => '000000',
        'phone' => '9999999999',
        'email' => strtolower($code).'@example.test',
    ]);
}

function recordsExportUser(
    string $role,
    Branch $branch,
    string $email
): User {
    $user = User::query()->create([
        'name' => ucfirst($role).' User',
        'first_name' => ucfirst($role),
        'last_name' => 'Exporter',
        'email' => $email,
        'password' => Hash::make('password'),
        'branch_id' => $branch->id,
        'is_active' => true,
        'employment_status' => 'active',
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->assignRole($role);

    return $user;
}

function recordsExportCandidate(Branch $branch, string $email): Candidate
{
    return Candidate::query()->create([
        'first_name' => 'Export',
        'last_name' => 'Candidate',
        'email' => $email,
        'phone' => (string) random_int(7000000000, 9999999999),
        'position_applied' => 'Support Executive',
        'branch_id' => $branch->id,
    ]);
}

function recordsExportArchiveContents(string $archivePath): array
{
    $archive = new ZipArchive;
    $opened = $archive->open($archivePath) === true;

    try {
        expect($opened)->toBeTrue();

        $contents = [];
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            $contents[$name] = $archive->getFromIndex($index);
        }

        return $contents;
    } finally {
        if ($opened) {
            $archive->close();
        }
        if (is_file($archivePath)) {
            unlink($archivePath);
        }
    }
}

test('users without documents export permission cannot view or download records', function () {
    $branch = recordsExportBranch('Export Branch', 'EXP');
    $employee = recordsExportUser(
        'employee',
        $branch,
        'export-employee@example.test'
    );

    $this->actingAs($employee)
        ->get(route('records.export.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('records.export.download'))
        ->assertForbidden();
});

test('hr export includes only branch employee and candidate data and archives uploaded files', function () {
    Storage::fake('public');

    $branch = recordsExportBranch('Export Branch', 'EXP');
    $otherBranch = recordsExportBranch('Other Branch', 'OTH');
    $hr = recordsExportUser('hr_recruiter', $branch, 'export-hr@example.test');
    recordsExportUser('employee', $branch, 'branch-employee@example.test');
    recordsExportUser('employee', $otherBranch, 'other-employee@example.test');

    $candidate = recordsExportCandidate($branch, 'branch-candidate@example.test');
    recordsExportCandidate($otherBranch, 'other-candidate@example.test');
    $requirement = DocumentRequirement::query()->firstOrFail();
    $filePath = "candidate_documents/{$candidate->id}/identity.pdf";
    Storage::disk('public')->put($filePath, 'export-test-document');
    CandidateDocument::query()->create([
        'candidate_id' => $candidate->id,
        'document_requirement_id' => $requirement->id,
        'file_name' => 'identity.pdf',
        'file_path' => $filePath,
        'mime_type' => 'application/pdf',
        'file_size' => 20,
        'uploaded_by' => $hr->id,
    ]);

    $summary = app(RecordsExportService::class)->summary($hr);
    expect($summary['employees'])->toBe(2)
        ->and($summary['candidates'])->toBe(1)
        ->and($summary['candidate_documents'])->toBe(1);

    $archivePath = app(RecordsExportService::class)->createArchive($hr);
    $archive = new ZipArchive;
    try {
        expect($archive->open($archivePath))->toBeTrue();

        $employees = $archive->getFromName('employees.csv');
        $candidates = $archive->getFromName('candidates.csv');
        $manifest = $archive->getFromName('documents.csv');

        expect($employees)->toContain('branch-employee@example.test')
            ->not->toContain('other-employee@example.test')
            ->not->toContain('password')
            ->not->toContain('remember_token')
            ->toContain('email_verified_at')
            ->and($candidates)->toContain('branch-candidate@example.test')
            ->not->toContain('other-candidate@example.test')
            ->and($manifest)->toContain('candidate_document')
            ->toContain('included');

        $fileEntries = [];
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $fileEntries[] = $archive->getNameIndex($index);
        }
        $documentEntry = collect($fileEntries)
            ->first(fn (string $entry) => str_starts_with($entry, "documents/candidates/{$candidate->id}/"));

        expect($documentEntry)->not->toBeNull()
            ->and($archive->getFromName($documentEntry))->toBe('export-test-document');

    } finally {
        $archive->close();
        if (is_file($archivePath)) {
            unlink($archivePath);
        }
    }
});

test('individual exports contain only the selected employee or candidate and their files', function () {
    Storage::fake('public');

    $branch = recordsExportBranch('Export Branch', 'EXP');
    $otherBranch = recordsExportBranch('Other Branch', 'OTH');
    $hr = recordsExportUser('hr_recruiter', $branch, 'individual-export-hr@example.test');
    $employee = recordsExportUser('employee', $branch, 'selected-employee@example.test');
    recordsExportUser('employee', $branch, 'other-employee@example.test');
    $candidate = recordsExportCandidate($branch, 'selected-candidate@example.test');
    $otherCandidate = recordsExportCandidate($branch, 'other-candidate@example.test');
    recordsExportCandidate($otherBranch, 'outside-branch-candidate@example.test');

    $avatarPath = "employee-avatars/{$employee->id}/avatar.png";
    Storage::disk('public')->put($avatarPath, 'selected-employee-avatar');
    $employee->forceFill(['avatar' => $avatarPath])->save();

    $requirement = DocumentRequirement::query()->firstOrFail();
    $documentPath = "candidate_documents/{$candidate->id}/identity.pdf";
    Storage::disk('public')->put($documentPath, 'selected-candidate-document');
    CandidateDocument::query()->create([
        'candidate_id' => $candidate->id,
        'document_requirement_id' => $requirement->id,
        'file_name' => 'identity.pdf',
        'file_path' => $documentPath,
        'mime_type' => 'application/pdf',
        'file_size' => 25,
        'uploaded_by' => $hr->id,
    ]);

    $service = app(RecordsExportService::class);
    $employeeContents = recordsExportArchiveContents(
        $service->createEmployeeArchive($hr, $employee->id)
    );
    expect($employeeContents)->toHaveKey('employees.csv')
        ->not->toHaveKey('candidates.csv')
        ->and($employeeContents['employees.csv'])
        ->toContain('selected-employee@example.test')
        ->not->toContain('other-employee@example.test')
        ->and($employeeContents)->toHaveKey('documents.csv')
        ->and(collect($employeeContents)->contains('selected-employee-avatar'))->toBeTrue()
        ->and(collect($employeeContents)->contains('selected-candidate-document'))->toBeFalse();

    $candidateContents = recordsExportArchiveContents(
        $service->createCandidateArchive($hr, $candidate->id)
    );
    expect($candidateContents)->toHaveKey('candidates.csv')
        ->not->toHaveKey('employees.csv')
        ->and($candidateContents['candidates.csv'])
        ->toContain('selected-candidate@example.test')
        ->not->toContain('other-candidate@example.test')
        ->not->toContain('outside-branch-candidate@example.test')
        ->and(collect($candidateContents)->contains('selected-candidate-document'))->toBeTrue()
        ->and(collect($candidateContents)->contains('selected-employee-avatar'))->toBeFalse();

    $this->actingAs($hr)
        ->get(route('records.export.candidate', $otherCandidate->id))
        ->assertNotFound();
});
