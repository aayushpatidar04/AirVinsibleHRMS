<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateFormSubmission;
use App\Models\CandidateIntentLetter;
use App\Models\CandidateOffer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class RecordsExportService
{
    public function summary(User $user): array
    {
        return [
            'employees' => $this->employeeQuery($user)->count(),
            'candidates' => $this->candidateQuery($user)->count(),
            'candidate_documents' => CandidateDocument::withTrashed()
                ->whereHas('candidate', fn (Builder $query) => $this->scopeCandidateBranch($query, $user))
                ->count(),
        ];
    }

    public function createArchive(User $user): string
    {
        $workspace = storage_path('app/temp/records-export/'.Str::uuid());
        $archivePath = storage_path('app/records-export-'.Str::uuid().'.zip');
        File::ensureDirectoryExists($workspace);

        $zip = new ZipArchive;
        $opened = false;

        try {
            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the records export archive.');
            }
            $opened = true;

            $employeesCsv = $workspace.DIRECTORY_SEPARATOR.'employees.csv';
            $candidatesCsv = $workspace.DIRECTORY_SEPARATOR.'candidates.csv';
            $documentsCsv = $workspace.DIRECTORY_SEPARATOR.'documents.csv';

            $this->writeEmployeesCsv($employeesCsv, $user);
            $this->writeCandidatesCsv($candidatesCsv, $user);
            $this->writeDocumentsCsv($documentsCsv, $workspace, $zip, $user);

            foreach ([
                'employees.csv' => $employeesCsv,
                'candidates.csv' => $candidatesCsv,
                'documents.csv' => $documentsCsv,
            ] as $entryName => $filePath) {
                if (! $zip->addFile($filePath, $entryName)) {
                    throw new RuntimeException("Unable to add {$entryName} to the export archive.");
                }
            }

            if (! $zip->close()) {
                $opened = false;
                throw new RuntimeException('Unable to finalize the records export archive.');
            }
            $opened = false;
        } catch (Throwable $exception) {
            if ($opened) {
                $zip->close();
            }

            File::deleteDirectory($workspace);
            if (is_file($archivePath)) {
                unlink($archivePath);
            }

            throw $exception;
        }

        File::deleteDirectory($workspace);

        return $archivePath;
    }

    private function employeeQuery(User $user): Builder
    {
        return User::withTrashed()
            ->with([
                'branch:id,name',
                'manager:id,first_name,last_name',
                'roles:id,name',
            ])
            ->when(
                ! $user->hasRole('admin'),
                fn (Builder $query) => $this->scopeBranch($query, $user)
            );
    }

    private function candidateQuery(User $user): Builder
    {
        return Candidate::withTrashed()
            ->with('branch:id,name')
            ->when(
                ! $user->hasRole('admin'),
                fn (Builder $query) => $this->scopeBranch($query, $user)
            );
    }

    private function scopeBranch(Builder $query, User $user): Builder
    {
        if (! $user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('branch_id', $user->branch_id);
    }

    private function writeEmployeesCsv(string $path, User $user): void
    {
        $headers = array_values(array_filter(
            Schema::getColumnListing('users'),
            fn (string $column) => ! preg_match(
                '/password|(^|_)token($|_)|secret|recovery_code/i',
                $column
            )
        ));
        array_push($headers, 'branch_name', 'manager_name', 'roles');

        $this->writeCsv($path, $headers, function ($stream) use ($user, $headers): void {
            $this->employeeQuery($user)->chunkById(500, function ($employees) use ($stream, $headers): void {
                foreach ($employees as $employee) {
                    $values = [
                        ...$employee->getAttributes(),
                        'branch_name' => $employee->branch?->name,
                        'manager_name' => $employee->manager?->full_name,
                        'roles' => $employee->roles->pluck('name')->implode('|'),
                    ];
                    $this->writeCsvRow(
                        $stream,
                        array_map(fn (string $header) => $values[$header] ?? null, $headers)
                    );
                }
            });
        });
    }

    private function writeCandidatesCsv(string $path, User $user): void
    {
        $headers = [
            ...Schema::getColumnListing('candidates'),
            'branch_name',
        ];

        $this->writeCsv($path, $headers, function ($stream) use ($user, $headers): void {
            $this->candidateQuery($user)->chunkById(500, function ($candidates) use ($stream, $headers): void {
                foreach ($candidates as $candidate) {
                    $values = [
                        ...$candidate->getAttributes(),
                        'branch_name' => $candidate->branch?->name,
                    ];
                    $this->writeCsvRow(
                        $stream,
                        array_map(fn (string $header) => $values[$header] ?? null, $headers)
                    );
                }
            });
        });
    }

    private function writeDocumentsCsv(
        string $path,
        string $workspace,
        ZipArchive $zip,
        User $user
    ): void {
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Unable to create the documents manifest.');
        }

        try {
            fwrite($stream, "\xEF\xBB\xBF");
            $this->writeCsvRow($stream, [
                'record_type',
                'employee_id',
                'candidate_id',
                'record_id',
                'document_name',
                'original_file_name',
                'storage_disk',
                'storage_path',
                'archive_path',
                'status',
            ]);

            $addFile = function (
                string $recordType,
                ?int $employeeId,
                ?int $candidateId,
                int|string $recordId,
                string $documentName,
                string $originalName,
                string $disk,
                string $storagePath,
                string $archiveDirectory
            ) use ($stream, $workspace, $zip): void {
                $safeName = $this->safeFilename($originalName);
                $safeRecordId = $this->safeFilename((string) $recordId);
                $archivePath = "{$archiveDirectory}/{$safeRecordId}-{$safeName}";
                $status = 'missing';

                if ($this->isSafeStoragePath($storagePath) && Storage::disk($disk)->exists($storagePath)) {
                    $stagedPath = $workspace.DIRECTORY_SEPARATOR.Str::uuid().'-'.$safeName;
                    $input = Storage::disk($disk)->readStream($storagePath);
                    $output = fopen($stagedPath, 'wb');

                    if ($input === false || $output === false) {
                        if (is_resource($input)) {
                            fclose($input);
                        }
                        if (is_resource($output)) {
                            fclose($output);
                        }
                        throw new RuntimeException("Unable to read {$documentName} for export.");
                    }

                    try {
                        if (stream_copy_to_stream($input, $output) === false) {
                            throw new RuntimeException("Unable to stage {$documentName} for export.");
                        }
                    } finally {
                        fclose($input);
                        fclose($output);
                    }

                    if (! $zip->addFile($stagedPath, $archivePath)) {
                        throw new RuntimeException("Unable to add {$documentName} to the export archive.");
                    }

                    $status = 'included';
                }

                $this->writeCsvRow($stream, [
                    $recordType,
                    $employeeId,
                    $candidateId,
                    $recordId,
                    $documentName,
                    $originalName,
                    $disk,
                    $storagePath,
                    $status === 'included' ? $archivePath : null,
                    $status,
                ]);
            };

            $this->employeeQuery($user)
                ->whereNotNull('avatar')
                ->chunkById(500, function ($employees) use ($addFile): void {
                    foreach ($employees as $employee) {
                        $addFile(
                            'employee_avatar',
                            $employee->id,
                            null,
                            $employee->id,
                            'Employee avatar',
                            basename($employee->avatar),
                            'public',
                            $employee->avatar,
                            "documents/employees/{$employee->id}"
                        );
                    }
                });

            CandidateDocument::withTrashed()
                ->with('documentRequirement:id,name')
                ->whereHas('candidate', fn (Builder $query) => $this->scopeCandidateBranch($query, $user))
                ->chunkById(500, function ($documents) use ($addFile): void {
                    foreach ($documents as $document) {
                        $addFile(
                            'candidate_document',
                            null,
                            $document->candidate_id,
                            $document->id,
                            $document->documentRequirement?->name ?? 'Candidate document',
                            $document->file_name,
                            'public',
                            $document->file_path,
                            "documents/candidates/{$document->candidate_id}"
                        );
                    }
                });

            CandidateFormSubmission::query()
                ->whereHas('candidate', fn (Builder $query) => $this->scopeCandidateBranch($query, $user))
                ->chunkById(500, function ($submissions) use ($addFile): void {
                    foreach ($submissions as $submission) {
                        if ($submission->resume_path) {
                            $addFile(
                                'candidate_resume',
                                null,
                                $submission->candidate_id,
                                $submission->id,
                                'Candidate resume',
                                $submission->resume_original_name ?: basename($submission->resume_path),
                                'public',
                                $submission->resume_path,
                                "documents/candidates/{$submission->candidate_id}/submissions"
                            );
                        }

                        foreach ($this->uploadedFiles($submission->submission_data ?? []) as $upload) {
                            $addFile(
                                'candidate_form_upload',
                                null,
                                $submission->candidate_id,
                                $submission->id.'-'.$upload['field'],
                                $upload['field'],
                                $upload['name'] ?: basename($upload['path']),
                                'public',
                                $upload['path'],
                                "documents/candidates/{$submission->candidate_id}/submissions"
                            );
                        }
                    }
                });

            CandidateOffer::withTrashed()
                ->whereHas('candidate', fn (Builder $query) => $this->scopeCandidateBranch($query, $user))
                ->whereNotNull('pdf_path')
                ->chunkById(500, function ($offers) use ($addFile): void {
                    foreach ($offers as $offer) {
                        $addFile(
                            'candidate_offer',
                            null,
                            $offer->candidate_id,
                            $offer->id,
                            'Candidate offer',
                            basename($offer->pdf_path),
                            'public',
                            $offer->pdf_path,
                            "documents/candidates/{$offer->candidate_id}/offers"
                        );
                    }
                });

            CandidateIntentLetter::query()
                ->whereHas('candidate', fn (Builder $query) => $this->scopeCandidateBranch($query, $user))
                ->chunkById(500, function ($letters) use ($addFile): void {
                    foreach ($letters as $letter) {
                        foreach ([
                            'pdf_path' => 'Candidate intent letter',
                            'acknowledged_pdf_path' => 'Acknowledged intent letter',
                        ] as $field => $documentName) {
                            if (! $letter->{$field}) {
                                continue;
                            }

                            $addFile(
                                'candidate_intent_letter',
                                null,
                                $letter->candidate_id,
                                $letter->id.'-'.$field,
                                $documentName,
                                basename($letter->{$field}),
                                'local',
                                $letter->{$field},
                                "documents/candidates/{$letter->candidate_id}/intent-letters"
                            );
                        }
                    }
                });
        } finally {
            fclose($stream);
        }
    }

    private function scopeCandidateBranch(Builder $query, User $user): Builder
    {
        $query->withTrashed();

        if ($user->hasRole('admin')) {
            return $query;
        }

        return $this->scopeBranch($query, $user);
    }

    private function uploadedFiles(array $value, string $field = ''): array
    {
        $files = [];

        if (isset($value['path']) && is_string($value['path'])) {
            $files[] = [
                'path' => $value['path'],
                'name' => is_string($value['name'] ?? null) ? $value['name'] : '',
                'field' => $field ?: 'form_upload',
            ];
        }

        foreach ($value as $key => $nested) {
            if (is_array($nested)) {
                $files = [
                    ...$files,
                    ...$this->uploadedFiles($nested, is_string($key) ? $key : $field),
                ];
            }
        }

        return $files;
    }

    private function writeCsv(string $path, array $headers, callable $writeRows): void
    {
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Unable to create a CSV export file.');
        }

        try {
            fwrite($stream, "\xEF\xBB\xBF");
            $this->writeCsvRow($stream, $headers);
            $writeRows($stream);
        } finally {
            fclose($stream);
        }
    }

    private function writeCsvRow($stream, array $values): void
    {
        $values = array_map(fn ($value) => $this->csvValue($value), $values);

        if (fputcsv($stream, $values, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write a CSV export row.');
        }
    }

    private function csvValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            return '';
        } elseif (! is_string($value)) {
            $value = (string) $value;
        }

        if (! is_numeric($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        return $filename !== '' ? $filename : 'file';
    }

    private function isSafeStoragePath(string $path): bool
    {
        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_starts_with($path, '\\')
            && ! str_contains($path, ':')
            && ! preg_match('/(^|[\\\\\/])\.\.?([\\\\\/]|$)/', $path);
    }
}
