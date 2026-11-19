<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Workforce\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Workforce/Reports/Index', [
            'groups' => ReportService::GROUPS,
            'reports' => $this->reports->available($request->user()),
        ]);
    }

    public function show(Request $request, string $report): Response|StreamedResponse
    {
        abort_unless(isset(ReportService::REPORTS[$report]), 404);

        $viewer = $request->user();
        abort_unless($viewer->hasRole('admin') || $viewer->hasAnyPermission($this->reports->permissionFor($report)), 403);

        $filters = $request->only(['from', 'to', 'client_id', 'department', 'search', 'run_id']);
        $data = $this->reports->run($report, $filters, $viewer);

        if ($request->query('export') === 'csv') {
            return $this->csv($report, $data);
        }

        $title = ReportService::REPORTS[$report][0];

        return Inertia::render('Workforce/Reports/Show', [
            'report' => ['key' => $report, 'title' => $title, 'description' => ReportService::REPORTS[$report][2], 'group' => ReportService::REPORTS[$report][1]],
            'columns' => $data['columns'],
            'rows' => array_slice($data['rows'], 0, 500),
            'total' => count($data['rows']),
            'filters' => [
                'from' => $filters['from'] ?? today()->startOfMonth()->toDateString(),
                'to' => $filters['to'] ?? today()->toDateString(),
                'client_id' => $filters['client_id'] ?? '',
                'department' => $filters['department'] ?? '',
                'search' => $filters['search'] ?? '',
                'run_id' => $filters['run_id'] ?? '',
            ],
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'departments' => User::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'runs' => ReportService::REPORTS[$report][1] === 'payroll' ? PayrollRun::query()->latest('id')->limit(40)->get(['id', 'run_number']) : [],
        ]);
    }

    private function csv(string $report, array $data): StreamedResponse
    {
        $filename = $report . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
            fputcsv($out, $data['columns']);

            foreach ($data['rows'] as $row) {
                fputcsv($out, array_map([$this, 'safe'], $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet formula injection from names, reasons and notes. */
    private function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
