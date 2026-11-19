<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\Workforce\PayslipService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PayslipController extends Controller
{
    /** Employees only ever see their own payslips, and only after they are published. */
    public function index(Request $request): Response
    {
        $payslips = Payslip::query()
            ->visibleToEmployee()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('period_start')
            ->get()
            ->map(fn (Payslip $p) => [
                'id' => $p->id,
                'number' => $p->payslip_number,
                'period' => $p->snapshot['period']['name'] ?? $p->period_start->format('F Y'),
                'gross' => $p->gross_earnings,
                'deductions' => $p->total_deductions,
                'net' => $p->net_pay,
                'paid_days' => $p->snapshot['days']['paid'] ?? null,
                'published_at' => $p->published_at->format('d M Y'),
            ]);

        return Inertia::render('Employee/Payslips', ['payslips' => $payslips]);
    }

    public function download(Request $request, Payslip $payslip, PayslipService $service): HttpResponse
    {
        abort_unless($payslip->user_id === $request->user()->id && $payslip->isPublished(), 404);

        return response($service->pdf($payslip), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $payslip->payslip_number . '.pdf"',
        ]);
    }
}
