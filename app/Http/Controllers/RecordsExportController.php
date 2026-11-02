<?php

namespace App\Http\Controllers;

use App\Services\RecordsExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecordsExportController extends Controller
{
    public function index(
        Request $request,
        RecordsExportService $exportService
    ) {
        return Inertia::render('Records/Export', [
            'summary' => $exportService->summary($request->user()),
        ]);
    }

    public function download(
        Request $request,
        RecordsExportService $exportService
    ): BinaryFileResponse {
        $archivePath = $exportService->createArchive($request->user());

        return response()
            ->download($archivePath, 'hrms-records-'.now()->format('Ymd-His').'.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadEmployee(
        Request $request,
        RecordsExportService $exportService,
        int $employee
    ): BinaryFileResponse {
        $archivePath = $exportService->createEmployeeArchive(
            $request->user(),
            $employee
        );

        return response()
            ->download($archivePath, "employee-{$employee}-records.zip")
            ->deleteFileAfterSend(true);
    }

    public function downloadCandidate(
        Request $request,
        RecordsExportService $exportService,
        int $candidate
    ): BinaryFileResponse {
        $archivePath = $exportService->createCandidateArchive(
            $request->user(),
            $candidate
        );

        return response()
            ->download($archivePath, "candidate-{$candidate}-records.zip")
            ->deleteFileAfterSend(true);
    }
}
