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
}
