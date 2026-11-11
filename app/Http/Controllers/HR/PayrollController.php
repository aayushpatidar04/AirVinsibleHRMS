<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $query = PayrollRun::query()
            ->with(['client', 'payrollPeriod']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $runs = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Hr/Payroll/Index', [
            'runs' => $runs,
            'clients' => $clients,
            'statuses' => [
                'draft' => 'Draft',
                'calculated' => 'Calculated',
                'pending_review' => 'Pending Review',
                'pending_approval' => 'Pending Approval',
                'approved' => 'Approved',
                'payslips_published' => 'Payslips Published',
                'closed' => 'Closed',
            ],
            'filters' => $request->only(['client_id', 'status']),
        ]);
    }

    public function periods(Request $request)
    {
        $query = PayrollPeriod::query()->with('client');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $periods = $query->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Hr/Payroll/Periods', [
            'periods' => $periods,
            'clients' => $clients,
            'filters' => $request->only(['client_id']),
        ]);
    }

    public function storePeriod(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'attendance_cutoff_date' => 'required|date',
            'salary_processing_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        PayrollPeriod::create($data);

        return back()->with('success', 'Payroll period created successfully.');
    }

    public function components(Request $request)
    {
        $query = PayrollComponent::query()->with('client');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $components = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Hr/Payroll/Components', [
            'components' => $components,
            'clients' => $clients,
            'filters' => $request->only(['client_id', 'type']),
        ]);
    }

    public function storeComponent(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:payroll_components,code',
            'type' => 'required|in:earning,deduction',
            'calculation_method' => 'nullable|string|max:50',
            'default_value' => 'nullable|numeric|min:0',
            'percentage_base' => 'nullable|string|max:50',
            'is_statutory' => 'boolean',
            'is_active' => 'boolean',
            'formula' => 'nullable|string|max:500',
        ]);

        PayrollComponent::create($data);

        return back()->with('success', 'Payroll component created successfully.');
    }

    public function calculations(Request $request, PayrollRun $run)
    {
        $run->load(['calculations.user', 'calculations.client']);

        $calculations = $run->calculations()->orderBy('user_id')->paginate(15);

        return Inertia::render('Hr/Payroll/Calculations', [
            'run' => $run,
            'calculations' => $calculations,
        ]);
    }

    public function payslips(Request $request)
    {
        $query = \App\Models\Payslip::query()
            ->with(['user', 'client', 'payrollCalculation']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $payslips = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $users = User::active()->orderBy('first_name')->get();

        return Inertia::render('Hr/Payroll/Payslips', [
            'payslips' => $payslips,
            'clients' => $clients,
            'users' => $users,
            'filters' => $request->only(['client_id', 'user_id', 'is_published']),
        ]);
    }

    public function publishPayslip(Request $request, \App\Models\Payslip $payslip)
    {
        $data = $request->validate([
            'publish' => 'required|boolean',
        ]);

        if ($data['publish']) {
            $payslip->update([
                'is_published' => true,
                'published_at' => now(),
                'published_by' => auth()->id(),
            ]);
        } else {
            $payslip->update([
                'is_published' => false,
                'published_at' => null,
                'published_by' => null,
            ]);
        }

        return back()->with('success', 'Payslip ' . ($data['publish'] ? 'published' : 'unpublished') . '.');
    }
}
