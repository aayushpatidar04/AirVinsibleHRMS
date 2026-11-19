<?php

namespace App\Services\Workforce;

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Payslip;
use App\Support\LetterHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Builds payslips from a calculated run item and renders them as PDF.
 *
 * A payslip stores a frozen copy of everything it shows, so it never changes
 * when later policies, salary structures or employee details change.
 */
class PayslipService
{
    /** Creates one hidden payslip per paid employee; validates the totals against the run. */
    public function generate(PayrollRun $run): int
    {
        $run->loadMissing('period', 'client');
        $created = 0;

        $items = $run->items()->where('status', '!=', 'held')->with('user.branch')->get();

        foreach ($items as $item) {
            $payslip = $this->createFor($run, $item);
            $created++;

            if (abs($payslip->net_pay - $item->net_pay) > 0.001) {
                throw new RuntimeException('Payslip total does not match payroll for employee ' . $item->user->employee_id);
            }
        }

        if (abs($run->payslips()->whereNull('voided_at')->sum('net_pay') - $items->sum('net_pay')) > 0.01) {
            throw new RuntimeException('Payslip totals do not match the payroll run.');
        }

        return $created;
    }

    private function createFor(PayrollRun $run, PayrollRunItem $item): Payslip
    {
        $employee = $item->user;
        $period = $run->period;

        $version = Payslip::query()->where('payroll_run_id', $run->id)->where('user_id', $employee->id)->count() + 1;

        return Payslip::create([
            'payroll_run_id' => $run->id,
            'payroll_run_item_id' => $item->id,
            'user_id' => $employee->id,
            'client_id' => $run->client_id,
            'payslip_number' => sprintf('PS-%s-%s%s', $run->run_number, $employee->employee_id, $version > 1 ? '-R' . $version : ''),
            'period_start' => $period->start_date,
            'period_end' => $period->end_date,
            'gross_earnings' => $item->gross_earnings,
            'total_deductions' => $item->total_deductions,
            'net_pay' => $item->net_pay,
            'generated_at' => now(),
            'snapshot' => [
                'company' => [
                    'name' => config('company.legal_name') ?: config('company.name'),
                    'address' => LetterHelper::footer(),
                ],
                'employee' => [
                    'name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                    'designation' => $employee->designation,
                    'department' => $employee->department,
                    'client' => $run->client->name,
                    'branch' => $employee->branch?->name,
                    'date_of_joining' => $employee->date_of_joining?->format('d M Y'),
                ],
                'period' => ['name' => $period->name, 'start' => $period->start_date->format('d M Y'), 'end' => $period->end_date->format('d M Y')],
                'days' => [
                    'calendar' => $item->calendar_days,
                    'paid' => $item->payable_days,
                    'unpaid' => $item->lop_days,
                    'present' => $item->present_days,
                    'paid_leave' => $item->paid_leave_days,
                    'unpaid_leave' => $item->unpaid_leave_days,
                    'weekly_off' => $item->weekly_off_days,
                    'holidays' => $item->holiday_days,
                ],
                'overtime_hours' => round($item->overtime_minutes / 60, 2),
                'earnings' => collect($item->earnings)->map(fn ($l) => ['name' => $l['name'], 'amount' => $l['amount']])->all(),
                'deductions' => collect($item->deductions)->map(fn ($l) => ['name' => $l['name'], 'amount' => $l['amount']])->all(),
                'gross' => $item->gross_earnings,
                'total_deductions' => $item->total_deductions,
                'net' => $item->net_pay,
                'net_in_words' => self::inWords((int) round($item->net_pay)),
                'payment_status' => 'Pending',
            ],
        ]);
    }

    /** PDF bytes for a payslip (generated on demand and cached on disk). */
    public function pdf(Payslip $payslip): string
    {
        $disk = Storage::disk('local');

        if ($payslip->pdf_path && $disk->exists($payslip->pdf_path)) {
            return $disk->get($payslip->pdf_path);
        }

        $bytes = Pdf::loadView('pdf.payroll.payslip', [
            'payslip' => $payslip,
            's' => $payslip->snapshot,
            'logo' => LetterHelper::logoPath(),
        ])->setPaper('a4')->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans', 'dpi' => 96])->output();

        $path = 'payslips/' . $payslip->user_id . '/' . $payslip->payslip_number . '.pdf';
        $disk->put($path, $bytes);
        $payslip->update(['pdf_path' => $path]);

        return $bytes;
    }

    /** Indian-style number in words, e.g. 12171 => "Twelve Thousand One Hundred Seventy One Rupees Only". */
    public static function inWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero Rupees Only';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
            'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $below100 = fn (int $n) => $n < 20 ? $ones[$n] : trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
        $below1000 = fn (int $n) => trim(($n >= 100 ? $ones[intdiv($n, 100)] . ' Hundred ' : '') . $below100($n % 100));

        $parts = [];
        foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand']] as [$value, $name]) {
            if ($number >= $value) {
                $parts[] = $below100(intdiv($number, $value)) . ' ' . $name;
                $number %= $value;
            }
        }
        if ($number > 0) {
            $parts[] = $below1000($number);
        }

        return trim(implode(' ', $parts)) . ' Rupees Only';
    }
}
