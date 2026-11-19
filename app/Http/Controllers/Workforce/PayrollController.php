<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CandidateOffer;
use App\Models\Client;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalaryStructureItem;
use App\Models\User;
use App\Services\Workforce\PayslipService;
use App\Services\Workforce\WorkforceScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PayrollController extends Controller
{
    private const STATUTORY = ['pf_employee', 'pf_employer', 'esic_employee', 'esic_employer'];

    /* ───────────────────────── periods ───────────────────────── */

    public function periods(Request $request): Response
    {
        return Inertia::render('Workforce/Payroll/Periods', [
            'periods' => PayrollPeriod::query()->with('client:id,name')->withCount('runs')
                ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
                ->orderByDesc('start_date')->paginate(15)->withQueryString()
                ->through(fn ($p) => [
                    'id' => $p->id,
                    'client' => $p->client->name,
                    'name' => $p->name,
                    'range' => $p->start_date->format('d M') . ' – ' . $p->end_date->format('d M Y'),
                    'cutoff' => $p->attendance_cutoff_date->format('d M Y'),
                    'processing' => $p->salary_processing_date->format('d M Y'),
                    'status' => $p->status,
                    'runs' => $p->runs_count,
                ]),
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => ['client_id' => $request->query('client_id')],
            'canCreate' => $request->user()->can('payroll.generate'),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'attendance_cutoff_date' => ['required', 'date', 'after_or_equal:end_date'],
            'salary_processing_date' => ['required', 'date', 'after_or_equal:attendance_cutoff_date'],
        ]);

        $overlap = PayrollPeriod::query()->where('client_id', $data['client_id'])
            ->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists();

        if ($overlap) {
            return back()->withErrors(['start_date' => 'This client already has a payroll period overlapping those dates.']);
        }

        $period = PayrollPeriod::create($data + ['status' => 'open']);
        AuditLog::record('payroll.period_created', $period, [], $period->only(['client_id', 'name', 'start_date', 'end_date']), $request->user()->id);

        return back()->with('success', 'Payroll period created.');
    }

    /* ───────────────────────── salary structures ───────────────────────── */

    public function structures(Request $request): Response
    {
        $search = trim((string) $request->query('search'));

        $employees = WorkforceScope::employees($request->user(), 'payroll')
            ->whereNotNull('employee_id')->where('employment_status', 'active')
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%")))
            ->with('client:id,name')
            ->orderBy('employee_id')
            ->paginate(20)->withQueryString();

        $current = EmployeeSalaryStructure::query()->whereIn('user_id', $employees->pluck('id'))
            ->effectiveOn(today())->with('items')->get()->keyBy('user_id');

        return Inertia::render('Workforce/Payroll/Structures', [
            'employees' => $employees->through(function (User $u) use ($current) {
                $s = $current->get($u->id);

                return [
                    'id' => $u->id,
                    'employee_id' => $u->employee_id,
                    'name' => $u->full_name,
                    'client' => $u->client?->name,
                    'designation' => $u->designation,
                    'has_structure' => (bool) $s,
                    'gross' => $s?->monthlyGross(),
                    'effective_from' => $s?->effective_from->format('d M Y'),
                ];
            }),
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => ['client_id' => $request->query('client_id'), 'search' => $search],
        ]);
    }

    public function editStructure(Request $request, User $employee): Response
    {
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'payroll'), 403);

        $structures = EmployeeSalaryStructure::query()->where('user_id', $employee->id)->with('items')->orderByDesc('effective_from')->get();
        $current = $structures->first(fn ($s) => $s->effective_from->lte(today()) && (!$s->effective_to || $s->effective_to->gte(today()))) ?? $structures->first();

        $offer = $this->acceptedOffer($employee);
        $settings = $employee->client_id ? PayrollSetting::forClient($employee->client_id, today()) : null;

        return Inertia::render('Workforce/Payroll/StructureEdit', [
            'employee' => ['id' => $employee->id, 'name' => $employee->full_name, 'employee_id' => $employee->employee_id, 'client' => $employee->client?->name, 'joined' => $employee->date_of_joining?->toDateString()],
            'current' => $current ? $this->structureArray($current) : null,
            'history' => $structures->map(fn ($s) => [
                'id' => $s->id,
                'effective_from' => $s->effective_from->format('d M Y'),
                'effective_to' => $s->effective_to?->format('d M Y'),
                'gross' => $s->monthlyGross(),
                'from_offer' => $s->source_offer_id !== null,
                'notes' => $s->notes,
            ]),
            'offer' => $offer ? ['id' => $offer->id, 'number' => $offer->offer_number, 'designation' => $offer->designation, 'joining_date' => $offer->joining_date?->format('d M Y')] : null,
            'rates' => $settings?->only(['pf_employee_rate', 'pf_employer_rate', 'esic_employee_rate', 'esic_employer_rate', 'calculation_method']),
            'canEdit' => $request->user()->can('payroll.manage-salary-structure'),
        ]);
    }

    public function storeStructure(Request $request, User $employee): RedirectResponse
    {
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'payroll'), 403);

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.type' => ['required', 'in:earning,deduction,employer_contribution'],
            'items.*.calc_type' => ['required', 'in:fixed,percent_of_basic,percent_of_gross'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'items.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.prorate' => ['boolean'],
            'items.*.is_basic' => ['boolean'],
            'items.*.statutory_code' => ['nullable', 'in:' . implode(',', self::STATUTORY)],
        ]);

        $items = collect($data['items']);

        if (!$items->contains(fn ($i) => $i['type'] === 'earning')) {
            return back()->withErrors(['items' => 'Add at least one earning (for example Basic).']);
        }

        foreach ($items as $i) {
            if ($i['calc_type'] !== 'fixed' && $i['percentage'] === null) {
                return back()->withErrors(['items' => $i['name'] . ': enter the percentage.']);
            }
        }

        $from = Carbon::parse($data['effective_from'])->startOfDay();

        $later = EmployeeSalaryStructure::query()->where('user_id', $employee->id)->whereDate('effective_from', '>=', $from->toDateString())->exists();
        if ($later) {
            return back()->withErrors(['effective_from' => 'A salary structure already starts on or after that date. Choose a later date.']);
        }

        DB::transaction(function () use ($employee, $data, $items, $from, $request) {
            EmployeeSalaryStructure::query()->where('user_id', $employee->id)
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from->toDateString()))
                ->update(['effective_to' => $from->copy()->subDay()->toDateString()]);

            $structure = $this->createStructure($employee, $from, $items->all(), $request->user(), $data['notes'] ?? null, $data['hourly_rate'] ?? null);

            AuditLog::record('salary_structure.created', $structure, [], ['effective_from' => $from->toDateString(), 'gross' => $structure->monthlyGross()], $request->user()->id);
        });

        return redirect()->route('workforce.payroll.structures.edit', $employee)->with('success', 'New salary structure saved. Earlier structures are kept for history.');
    }

    /** Builds a structure from the offer the candidate accepted. */
    public function importFromOffer(Request $request, User $employee): RedirectResponse
    {
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'payroll'), 403);

        $offer = $this->acceptedOffer($employee);

        if (!$offer) {
            return back()->withErrors(['import' => 'No accepted offer is linked to this employee.']);
        }

        $from = ($offer->joining_date ?? $employee->date_of_joining ?? today())->copy()->startOfDay();

        if (EmployeeSalaryStructure::query()->where('user_id', $employee->id)->whereDate('effective_from', '>=', $from->toDateString())->exists()) {
            return back()->withErrors(['import' => 'A salary structure already exists from that date. Edit it instead.']);
        }

        $components = $offer->salaryComponents()->with('percentageOfComponent')->get()->filter(fn ($c) => $c->show_in_offer !== false);

        $items = $components->map(function ($c) {
            $name = $c->component_name;
            $isBasic = $c->component_type === 'earning' && (bool) preg_match('/^basic/i', $name);
            $parentName = $c->percentageOfComponent?->component_name ?? '';
            $calc = 'fixed';

            if ($c->calculation_type === 'percentage' && $c->percentage !== null) {
                $calc = preg_match('/basic/i', $parentName) ? 'percent_of_basic' : 'fixed';
            }

            return [
                'name' => $name,
                'type' => $c->component_type,
                'calc_type' => $calc,
                'amount' => $calc === 'fixed' ? round((float) $c->monthly_amount, 2) : 0,
                'percentage' => $calc === 'fixed' ? null : (float) $c->percentage,
                'prorate' => !preg_match('/professional\s*tax/i', $name),
                'is_basic' => $isBasic,
                'statutory_code' => $this->guessStatutory($name, $c->component_type),
            ];
        })->values()->all();

        DB::transaction(function () use ($employee, $offer, $from, $items, $request) {
            $structure = $this->createStructure($employee, $from, $items, $request->user(), 'Imported from offer ' . $offer->offer_number, null, $offer->id);
            AuditLog::record('salary_structure.imported', $structure, [], ['offer' => $offer->offer_number, 'gross' => $structure->monthlyGross()], $request->user()->id);
        });

        return back()->with('success', 'Salary structure imported from offer ' . $offer->offer_number . '. Please review it before running payroll.');
    }

    /* ───────────────────────── settings ───────────────────────── */

    public function settings(Request $request): Response
    {
        $clients = Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $clientId = (int) ($request->query('client_id') ?: $clients->first()?->id);

        $current = $clientId ? PayrollSetting::forClient($clientId, today()) : null;

        return Inertia::render('Workforce/Payroll/Settings', [
            'clients' => $clients,
            'clientId' => $clientId,
            'settings' => $current?->only([
                'effective_from', 'calculation_method', 'divisor_mode', 'fixed_divisor', 'rounding', 'precision', 'overtime_base',
                'block_on_pending_attendance', 'block_on_pending_leave', 'separate_reviewer',
                'pf_enabled', 'pf_employee_rate', 'pf_employer_rate', 'pf_wage_ceiling',
                'esic_enabled', 'esic_employee_rate', 'esic_employer_rate', 'esic_gross_ceiling',
            ]),
            'stored' => $clientId ? PayrollSetting::query()->where('client_id', $clientId)->exists() : false,
            'history' => $clientId ? PayrollSetting::query()->where('client_id', $clientId)->orderByDesc('effective_from')->get(['id', 'effective_from', 'calculation_method', 'divisor_mode', 'fixed_divisor'])
                ->map(fn ($s) => ['id' => $s->id, 'effective_from' => $s->effective_from->format('d M Y'), 'method' => $s->calculation_method, 'divisor' => $s->divisor_mode === 'fixed' ? $s->fixed_divisor : 'calendar days']) : [],
            'canEdit' => $request->user()->can('payroll.manage-salary-structure'),
        ]);
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'effective_from' => ['required', 'date'],
            'calculation_method' => ['required', 'in:monthly_fixed,hourly'],
            'divisor_mode' => ['required', 'in:calendar_days,fixed'],
            'fixed_divisor' => ['required', 'integer', 'between:20,31'],
            'rounding' => ['required', 'in:nearest,up,down'],
            'precision' => ['required', 'integer', 'between:0,2'],
            'overtime_base' => ['required', 'in:basic,gross'],
            'block_on_pending_attendance' => ['boolean'],
            'block_on_pending_leave' => ['boolean'],
            'separate_reviewer' => ['boolean'],
            'pf_enabled' => ['boolean'],
            'pf_employee_rate' => ['required', 'numeric', 'between:0,100'],
            'pf_employer_rate' => ['required', 'numeric', 'between:0,100'],
            'pf_wage_ceiling' => ['nullable', 'numeric', 'min:0'],
            'esic_enabled' => ['boolean'],
            'esic_employee_rate' => ['required', 'numeric', 'between:0,100'],
            'esic_employer_rate' => ['required', 'numeric', 'between:0,100'],
            'esic_gross_ceiling' => ['required', 'numeric', 'min:0'],
        ]);

        $row = PayrollSetting::query()->where('client_id', $data['client_id'])->whereDate('effective_from', $data['effective_from'])->first();
        $old = $row?->toArray() ?? [];

        $row = $row ? tap($row)->update($data) : PayrollSetting::create($data);

        AuditLog::record('payroll.settings_saved', $row, $old, $data, $request->user()->id);

        return back()->with('success', 'Payroll settings saved. Runs already approved keep the settings they used.');
    }

    /* ───────────────────────── payslips ───────────────────────── */

    public function payslips(Request $request): Response
    {
        $viewer = $request->user();
        $ids = WorkforceScope::employees($viewer, 'payroll')->pluck('users.id');
        $search = trim((string) $request->query('search'));

        $payslips = Payslip::query()
            ->whereIn('user_id', $ids)->whereNull('voided_at')
            ->when($request->query('run_id'), fn ($q, $r) => $q->where('payroll_run_id', $r))
            ->when($request->query('status') === 'published', fn ($q) => $q->whereNotNull('published_at'))
            ->when($request->query('status') === 'hidden', fn ($q) => $q->whereNull('published_at'))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%")))
            ->with('user:id,first_name,last_name,employee_id', 'run:id,run_number')
            ->latest('id')->paginate(25)->withQueryString()
            ->through(fn (Payslip $p) => [
                'id' => $p->id,
                'number' => $p->payslip_number,
                'employee' => $p->user->full_name,
                'employee_id' => $p->user->employee_id,
                'period' => $p->snapshot['period']['name'] ?? '',
                'run' => $p->run->run_number,
                'net' => $p->net_pay,
                'published' => $p->isPublished(),
                'published_at' => $p->published_at?->format('d M Y, h:i A'),
            ]);

        $base = Payslip::query()->whereIn('user_id', $ids)->whereNull('voided_at');

        return Inertia::render('Workforce/Payroll/Payslips', [
            'payslips' => $payslips,
            'filters' => ['run_id' => $request->query('run_id'), 'status' => $request->query('status'), 'search' => $search],
            'runs' => PayrollRun::query()->whereIn('status', ['approved', 'payslips_published', 'closed'])->latest('id')->limit(30)->get(['id', 'run_number']),
            'counts' => ['published' => (clone $base)->whereNotNull('published_at')->count(), 'hidden' => (clone $base)->whereNull('published_at')->count()],
        ]);
    }

    public function downloadPayslip(Request $request, Payslip $payslip, PayslipService $service): HttpResponse
    {
        abort_unless(WorkforceScope::canSee($request->user(), $payslip->user, 'payroll'), 403);

        AuditLog::record('payslip.downloaded', $payslip, [], ['by' => $request->user()->id], $request->user()->id);

        return response($service->pdf($payslip), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $payslip->payslip_number . '.pdf"',
        ]);
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function acceptedOffer(User $employee): ?CandidateOffer
    {
        return CandidateOffer::query()
            ->whereHas('candidate', fn ($q) => $q->where('user_id', $employee->id))
            ->where('status', CandidateOffer::STATUS_ACCEPTED)
            ->latest('id')->first();
    }

    private function createStructure(User $employee, Carbon $from, array $items, User $by, ?string $notes, ?float $hourly = null, ?int $offerId = null): EmployeeSalaryStructure
    {
        $structure = EmployeeSalaryStructure::create([
            'user_id' => $employee->id,
            'client_id' => $employee->client_id,
            'source_offer_id' => $offerId,
            'effective_from' => $from->toDateString(),
            'hourly_rate' => $hourly,
            'notes' => $notes,
            'created_by' => $by->id,
        ]);

        foreach (array_values($items) as $index => $i) {
            SalaryStructureItem::create([
                'structure_id' => $structure->id,
                'name' => $i['name'],
                'code' => Str::upper(Str::limit(Str::slug($i['name'], '_'), 36, '')) ?: 'ITEM' . $index,
                'type' => $i['type'],
                'calc_type' => $i['calc_type'],
                'amount' => $i['calc_type'] === 'fixed' ? ($i['amount'] ?? 0) : 0,
                'percentage' => $i['calc_type'] === 'fixed' ? null : ($i['percentage'] ?? 0),
                'statutory_code' => $i['statutory_code'] ?? null,
                'prorate' => (bool) ($i['prorate'] ?? true),
                'is_basic' => (bool) ($i['is_basic'] ?? false),
                'sort_order' => $index,
            ]);
        }

        return $structure->load('items');
    }

    private function guessStatutory(string $name, string $type): ?string
    {
        $isEmployer = $type === 'employer_contribution' || preg_match('/company|employer/i', $name);
        $isEmployee = $type === 'deduction' || preg_match('/employee/i', $name);

        if (preg_match('/\b(pf|provident)\b/i', $name)) {
            return $isEmployer && $type === 'employer_contribution' ? 'pf_employer' : ($isEmployee && $type === 'deduction' ? 'pf_employee' : null);
        }

        if (preg_match('/\b(esic|esi)\b/i', $name)) {
            return $isEmployer && $type === 'employer_contribution' ? 'esic_employer' : ($isEmployee && $type === 'deduction' ? 'esic_employee' : null);
        }

        return null;
    }

    private function structureArray(EmployeeSalaryStructure $s): array
    {
        return [
            'id' => $s->id,
            'effective_from' => $s->effective_from->toDateString(),
            'hourly_rate' => $s->hourly_rate,
            'items' => $s->items->map(fn ($i) => [
                'name' => $i->name, 'type' => $i->type, 'calc_type' => $i->calc_type,
                'amount' => $i->amount, 'percentage' => $i->percentage, 'prorate' => $i->prorate,
                'is_basic' => $i->is_basic, 'statutory_code' => $i->statutory_code,
            ])->values(),
        ];
    }
}
