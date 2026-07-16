<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use App\Models\Branch;
use App\Models\InterviewRound;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /** All employees list */
    public function index(Request $request)
    {
        $employees = User::withTrashed()->with('branch', 'manager')
            ->withCount([
                'interviewProgress as total_interviews',
                'interviewProgress as pending_count' => fn($q) => $q->where('status', 'pending'),
            ])
            ->when(
                $request->search,
                fn($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
                    ->orWhere('employee_id', 'like', "%$s%")
                    ->orWhere('designation', 'like', "%$s%")
            )
            ->when($request->branch_id, fn($q, $b) => $q->where('branch_id', $b))
            ->when($request->department, fn($q, $d) => $q->where('department', $d))
            ->when($request->role, fn($q, $r) => $q->role($r))
            ->when($request->status === 'active', fn($q) => $q->where('employment_status', 'active'))
            ->when($request->status === 'inactive', fn($q) => $q->where('employment_status', '!=', 'active'))
            ->when($request->can_interview === 'yes', fn($q) => $q->where('can_interview', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees->through(fn($u) => $this->formatEmployee($u)),
            'branches' => Branch::active()->get(['id', 'name']),
            'roles' => Role::all(['name'])->pluck('name'),
            'departments' => User::select('department')->distinct()->whereNotNull('department')->pluck('department'),
            'filters' => $request->only(['search', 'branch_id', 'department', 'role', 'status', 'can_interview']),
            'employmentTypes' => ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'],
        ]);
    }

    /** Create new employee */
    public function create()
    {
        return Inertia::render('Admin/Employees/Create', [
            'branches' => Branch::active()->get(['id', 'name']),
            'roles' => Role::all(['name'])->pluck('name'),
            'managers' => User::active()->get()->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name, 'designation' => $u->designation]),
            'employmentTypes' => ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'],
        ]);
    }

    /** Store new employee */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'reporting_to' => 'nullable|exists:users,id',
            'date_of_joining' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'can_interview' => 'boolean',
        ]);

        $employee = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'employee_id' => $data['employee_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'department' => $data['department'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'reporting_to' => $data['reporting_to'] ?? null,
            'date_of_joining' => $data['date_of_joining'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'employment_type' => $data['employment_type'],
            'address' => $data['address'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'password' => Hash::make($data['password']),
            'can_interview' => $data['can_interview'] ?? false,
            'is_active' => true,
            'employment_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $employee->syncRoles($data['roles']);

        // If interviewer role assigned, set flag
        if (in_array('interviewer', $data['roles'])) {
            $employee->update(['can_interview' => true]);
        }

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    /** Show employee profile */
    public function show($id)
    {
        $employee = User::withTrashed()
            ->with(['branch', 'manager', 'reportees'])
            ->findOrFail($id);

        return Inertia::render('Admin/Employees/Show', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'employee_id' => $employee->employee_id,
                'designation' => $employee->designation,
                'department' => $employee->department,
                'branch' => $employee->branch?->name,
                'branch_id' => $employee->branch_id,
                'manager' => $employee->manager?->full_name,
                'date_of_joining' => $employee->date_of_joining?->format('d M Y'),
                'date_of_birth' => $employee->date_of_birth?->format('d M Y'),
                'employment_type' => $employee->employment_type,
                'employment_status' => $employee->employment_status,
                'address' => $employee->address,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_phone' => $employee->emergency_contact_phone,
                'can_interview' => $employee->can_interview,
                'is_active' => $employee->is_active,
                'roles' => $employee->getRoleNames(),
                'roles_label' => $employee->getRolesLabel(),
                'created_at' => $employee->created_at->format('d M Y'),
                'reportees_count' => $employee->reportees->count(),
                'deleted_at' => $employee->deleted_at,
                'interview_round_ids' => $employee->allowedRounds->pluck('id'),
            ],
            'interview_stats' => [
                'total' => $employee->interviewProgress()->count(),
                'pending' => $employee->interviewProgress()->where('status', 'pending')->count(),
                'completed' => $employee->interviewProgress()->where('status', 'completed')->count(),
                'rejected' => $employee->interviewProgress()->where('status', 'rejected')->count(),
            ],
            'recent_interviews' => $employee->interviewProgress()
                ->with('candidate', 'round')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'candidate' => $p->candidate->full_name,
                    'position' => $p->candidate->position_applied,
                    'round' => $p->round->name,
                    'status' => $p->status,
                    'rating' => $p->overall_rating,
                    'date' => $p->start_date?->format('d M Y'),
                ]),
            'all_roles' => Role::all(['name'])->pluck('name'),
            'branches' => Branch::active()->get(['id', 'name']),
            'interview_rounds' => InterviewRound::orderBy('sequence_number')->get(['id', 'name', 'description']),
        ]);
    }

    /** Show edit form */
    public function edit($id)
    {
        $employee = User::withTrashed()->findOrFail($id);
        return Inertia::render('Admin/Employees/Edit', [
            'employee' => array_merge($employee->toArray(), [
                'roles' => $employee->getRoleNames(),
                'date_of_joining' => $employee->date_of_joining?->format('Y-m-d'),
                'date_of_birth' => $employee->date_of_birth?->format('Y-m-d'),
            ]),
            'branches' => Branch::active()->get(['id', 'name']),
            'roles' => Role::all(['name'])->pluck('name'),
            'managers' => User::active()->where('id', '!=', $employee->id)
                ->get()->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name, 'designation' => $u->designation]),
            'employmentTypes' => ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern'],
        ]);
    }

    /** Update employee */
    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$employee->id}",
            'phone' => "required|string|max:20|unique:users,phone,{$employee->id}",
            'employee_id' => "nullable|string|max:50|unique:users,employee_id,{$employee->id}",
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'reporting_to' => 'nullable|exists:users,id',
            'date_of_joining' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,inactive,resigned,terminated',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'can_interview' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $employee->update($data);
        $employee->syncRoles($data['roles']);

        // Sync can_interview flag with role
        $hasInterviewerRole = in_array('interviewer', $data['roles']);
        $employee->update([
            'can_interview' => $data['can_interview'] || $hasInterviewerRole,
            'is_active' => $data['employment_status'] === 'active',
        ]);

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    /** Toggle interviewer role on/off for an employee */
    public function toggleInterviewer(User $employee)
    {
        if ($employee->hasRole('interviewer')) {
            $employee->removeRole('interviewer');
            $employee->update(['can_interview' => false]);
            $msg = "{$employee->full_name} is no longer an interviewer.";
        } else {
            $employee->assignRole('interviewer');
            $employee->update(['can_interview' => true]);
            $msg = "{$employee->full_name} can now conduct interviews.";
        }

        return back()->with('success', $msg);
    }

    /** Assign additional role */
    public function assignRole(Request $request, User $employee)
    {
        $data = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $employee->assignRole($data['role']);

        if ($data['role'] === 'interviewer') {
            $employee->update(['can_interview' => true]);
        }

        return back()->with('success', "Role '{$data['role']}' assigned to {$employee->full_name}.");
    }

    /** Remove a role */
    public function removeRole(Request $request, User $employee)
    {
        $data = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        // Prevent removing last role
        if ($employee->getRoleNames()->count() === 1) {
            return back()->with('error', 'Employee must have at least one role.');
        }

        $employee->removeRole($data['role']);

        if ($data['role'] === 'interviewer') {
            $employee->update(['can_interview' => false]);
        }

        return back()->with('success', "Role '{$data['role']}' removed.");
    }

    /** Reset password */
    public function resetPassword(Request $request, User $employee)
    {
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);
        $employee->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Password reset successfully.');
    }

    /** Soft delete */
    public function destroy(User $employee)
    {
        if ($employee->interviewProgress()->whereIn('status', ['pending', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot deactivate employee with active interviews.');
        }

        $employee->update(['employment_status' => 'inactive', 'is_active' => false]);
        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee deactivated.');
    }

    private function formatEmployee(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->full_name,
            'email' => $u->email,
            'phone' => $u->phone,
            'employee_id' => $u->employee_id,
            'designation' => $u->designation,
            'department' => $u->department,
            'branch' => $u->branch?->name,
            'roles' => $u->getRoleNames(),
            'roles_label' => $u->getRolesLabel(),
            'can_interview' => $u->can_interview,
            'employment_status' => $u->employment_status,
            'is_active' => $u->is_active,
            'total_interviews' => $u->total_interviews ?? 0,
            'pending_count' => $u->pending_count ?? 0,
        ];
    }

    public function restore($id)
    {
        $employee = User::withTrashed()->findOrFail($id);
        $employee->update(['employment_status' => 'active', 'is_active' => true, 'deleted_at' => null]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee Activated.');
    }

    public function syncInterviewRounds(Request $request, $id)
    {
        $request->validate([
            'round_ids' => 'nullable|array',
            'round_ids.*' => 'exists:interview_rounds,id',
        ]);

        $employee = User::findOrFail($id);

        // Only allow if employee can conduct interviews
        if (!$employee->can_interview) {
            return back()->with('error', 'Employee must be an interviewer first.');
        }

        $employee->allowedRounds()->sync($request->input('round_ids', []));

        return back()->with('success', 'Interview rounds updated successfully.');
    }

    public function import(Request $request)
    {
        if ($request->isMethod('get')) {
            return Inertia::render('Admin/Employees/Import', [
                'branches' => Branch::active()->get(['id', 'name']),
                'sampleUrl' => route('admin.employees.import.sample'),
            ]);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new EmployeesImport();

        try {
            Excel::import($import, $request->file('file'));

            $message = "{$import->importedCount} employees imported successfully.";

            if (!empty($import->errors)) {
                $errorCount = count($import->errors);
                $message .= " {$errorCount} rows failed.";
            }

            // DEBUG: Always include debug log in flash for troubleshooting
            return redirect()
                ->route('admin.employees.index')
                ->with('success', $message)
                ->with('importErrors', $import->errors)
                ->with('importDebug', $import->debugLog);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'email' => 'N/A',
                    'error' => implode(', ', $failure->errors()),
                ];
            }

            return redirect()
                ->route('admin.employees.index')
                ->with('error', 'Import validation failed. Check errors below.')
                ->with('importErrors', $errors)
                ->with('importDebug', $import->debugLog ?? ['Import failed before collection()']);

        } catch (\Exception $e) {
            \Log::error('Import exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()
                ->route('admin.employees.index')
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('importDebug', $import->debugLog ?? ['Exception before collection(): ' . $e->getMessage()]);
        }
    }

    /**
     * Download sample Excel file
     */
    public function downloadSample(): BinaryFileResponse
    {
        $path = storage_path('app/templates/employees_import_sample.xlsx');

        // Create sample file if it doesn't exist
        if (!file_exists($path)) {
            $this->createSampleFile($path);
        }

        return response()->download($path, 'employees_import_template.xlsx');
    }

    /**
     * Create sample Excel file programmatically
     */
    private function createSampleFile(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers — changed branch_id to branch_name
        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'employee_id',
            'designation',
            'department',
            'branch_name',
            'reporting_to_email',
            'date_of_joining',
            'date_of_birth',
            'employment_type',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'password',
            'roles',
            'can_interview'
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Sample data row 1
        $sample1 = [
            'Rahul',
            'Sharma',
            'rahul@company.com',
            '9876543210',
            'EMP001',
            'Senior Advisor',
            'Sales',
            'Mumbai HQ',
            'manager@company.com',
            '2024-01-15',
            '1990-05-20',
            'full_time',
            'Mumbai, India',
            'Ramesh Sharma',
            '9876543211',
            'password123',
            'employee,interviewer',
            'yes'
        ];
        foreach ($sample1 as $col => $value) {
            $sheet->setCellValueByColumnAndRow($col + 1, 2, $value);
        }

        // Sample data row 2
        $sample2 = [
            'Priya',
            'Patel',
            'priya@company.com',
            '9876543212',
            'EMP002',
            'Team Lead',
            'Operations',
            'Delhi Branch',
            'manager@company.com',
            '2024-02-01',
            '1992-08-15',
            'full_time',
            'Delhi, India',
            'Suresh Patel',
            '9876543213',
            'password123',
            'employee',
            'no'
        ];
        foreach ($sample2 as $col => $value) {
            $sheet->setCellValueByColumnAndRow($col + 1, 3, $value);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ];
        $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

        // Auto-width columns
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}