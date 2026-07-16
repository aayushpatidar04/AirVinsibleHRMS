<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Spatie\Permission\Models\Role;

class EmployeesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public $importedCount = 0;
    public $skippedRows = [];
    public $errors = [];
    public $debugLog = [];

    // Cache branches for performance
    private $branches = null;

    public function __construct()
    {
        // Pre-load all branches for case-insensitive matching
        $this->branches = Branch::all()->mapWithKeys(function ($branch) {
            return [strtolower(trim($branch->name)) => $branch->id];
        })->toArray();

        Log::info('EmployeesImport initialized', [
            'available_branches' => $this->branches,
            'branch_count' => count($this->branches),
        ]);

        $this->debugLog[] = 'Import initialized. Branches loaded: ' . count($this->branches);
    }

    /**
     * DEBUG: This method fires BEFORE collection(). Log raw rows.
     */
    public function collection(Collection $rows)
    {
        Log::info('collection() called', ['row_count' => $rows->count(), 'first_row' => $rows->first()?->toArray()]);

        $this->debugLog[] = 'collection() called with ' . $rows->count() . ' rows';

        if ($rows->isEmpty()) {
            Log::error('collection() received EMPTY rows!');
            $this->debugLog[] = 'ERROR: Empty rows received!';
            $this->errors[] = [
                'row' => 0,
                'email' => 'N/A',
                'error' => 'No data rows found in the uploaded file. Make sure the file has data below the header row.',
            ];
            return;
        }

        // DEBUG: Show first row keys to verify heading detection
        $firstRow = $rows->first();
        Log::info('First row keys', ['keys' => array_keys($firstRow->toArray())]);
        $this->debugLog[] = 'First row keys: ' . implode(', ', array_keys($firstRow->toArray()));

        // Get fallback branch (first active branch)
        $fallbackBranchId = Branch::active()->first()?->id;
        Log::info('Fallback branch ID', ['id' => $fallbackBranchId]);
        $this->debugLog[] = 'Fallback branch ID: ' . ($fallbackBranchId ?? 'NULL');

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because row 1 is header
            $rowArray = $row->toArray();

            Log::info("Processing row {$rowNum}", ['data' => $rowArray]);
            $this->debugLog[] = "Row {$rowNum}: " . json_encode($rowArray);

            try {
                // ─── VALIDATE REQUIRED FIELDS EXIST ───
                $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'employee_id', 'designation', 'department', 'date_of_birth'];
                foreach ($requiredFields as $field) {
                    if (!isset($rowArray[$field]) || empty($rowArray[$field])) {
                        throw new \Exception("Missing or empty required field: {$field}");
                    }
                }

                // ─── CHECK FOR DUPLICATE EMAIL ───
                if (User::where('email', $row['email'])->exists()) {
                    throw new \Exception("Email already exists: {$row['email']}");
                }

                // ─── CHECK FOR DUPLICATE EMPLOYEE_ID ───
                if (User::where('employee_id', $row['employee_id'])->exists()) {
                    throw new \Exception("Employee ID already exists: {$row['employee_id']}");
                }

                // Find reporting manager by email
                $reportingTo = null;
                if (!empty($row['reporting_to_email'])) {
                    $manager = User::where('email', $row['reporting_to_email'])->first();
                    $reportingTo = $manager?->id;
                    Log::info("Reporting manager lookup", ['email' => $row['reporting_to_email'], 'found' => $manager ? 'yes' : 'no']);
                }

                // Match branch by name (case-insensitive), fallback to first branch
                $branchId = $this->resolveBranchId($row['branch_name'] ?? null, $fallbackBranchId);
                Log::info("Branch resolved", ['input' => $row['branch_name'] ?? 'null', 'resolved_id' => $branchId]);

                if (!$branchId) {
                    throw new \Exception("Could not resolve branch: '{$row['branch_name']}'. Available: " . implode(', ', array_keys($this->branches)));
                }

                // Parse roles
                $roleNames = array_map('trim', explode(',', $row['roles'] ?? 'employee'));
                $validRoles = Role::whereIn('name', $roleNames)->pluck('name')->toArray();
                Log::info("Roles parsed", ['input' => $row['roles'] ?? 'employee', 'valid' => $validRoles]);

                if (empty($validRoles)) {
                    throw new \Exception("No valid roles found. Input: '{$row['roles']}'. Make sure roles exist in the database.");
                }

                // Parse dates
                $doj = $this->parseDate($row['date_of_joining']);
                $dob = $this->parseDate($row['date_of_birth']);
                Log::info("Dates parsed", ['doj' => $doj, 'dob' => $dob]);

                if (!$dob) {
                    throw new \Exception("Invalid date_of_birth format: '{$row['date_of_birth']}'. Use YYYY-MM-DD.");
                }

                // ─── CREATE EMPLOYEE ───
                $employee = User::create([
                    'first_name' => trim($row['first_name']),
                    'last_name' => trim($row['last_name']),
                    'email' => trim($row['email']),
                    'phone' => (string) $row['phone'],
                    'employee_id' => trim($row['employee_id']),
                    'designation' => trim($row['designation']),
                    'department' => trim($row['department']),
                    'branch_id' => $branchId,
                    'reporting_to' => $reportingTo,
                    'date_of_joining' => $doj,
                    'date_of_birth' => $dob,
                    'employment_type' => $row['employment_type'] ?? 'full_time',
                    'address' => $row['address'] ?? null,
                    'emergency_contact_name' => $row['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $row['emergency_contact_phone'] ?? null,
                    'password' => Hash::make($row['password'] ?? 'password123'),
                    'can_interview' => $this->toBoolean($row['can_interview'] ?? 'no'),
                    'is_active' => true,
                    'employment_status' => 'active',
                    'email_verified_at' => now(),
                ]);

                Log::info("User created", ['id' => $employee->id, 'email' => $employee->email]);
                $this->debugLog[] = "✅ Created user: {$employee->email} (ID: {$employee->id})";

                // Assign roles
                $employee->syncRoles($validRoles);

                // Auto-enable can_interview if interviewer role assigned
                if (in_array('interviewer', $validRoles)) {
                    $employee->update(['can_interview' => true]);
                }

                $this->importedCount++;
                Log::info("Import count incremented", ['total' => $this->importedCount]);

            } catch (\Illuminate\Database\QueryException $e) {
                $errorMsg = 'Database error: ' . $e->getMessage();
                Log::error("Row {$rowNum} database error", ['error' => $errorMsg]);
                $this->errors[] = [
                    'row' => $rowNum,
                    'email' => $row['email'] ?? 'N/A',
                    'error' => $errorMsg,
                ];
                $this->debugLog[] = "❌ Row {$rowNum} DB error: " . $e->getMessage();
            } catch (\Exception $e) {
                Log::error("Row {$rowNum} failed", ['error' => $e->getMessage()]);
                $this->errors[] = [
                    'row' => $rowNum,
                    'email' => $row['email'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
                $this->debugLog[] = "❌ Row {$rowNum}: " . $e->getMessage();
            }
        }

        Log::info('Import complete', [
            'imported' => $this->importedCount,
            'errors' => count($this->errors),
            'debug_count' => count($this->debugLog),
        ]);
        $this->debugLog[] = "Import complete. Imported: {$this->importedCount}, Errors: " . count($this->errors);
    }

    /**
     * Resolve branch ID from name (case-insensitive), fallback to first branch
     */
    private function resolveBranchId(?string $branchName, ?int $fallbackId): ?int
    {
        if (empty($branchName)) {
            Log::warning('Branch name is empty, using fallback');
            return $fallbackId;
        }

        $normalizedName = strtolower(trim((string) $branchName));

        // Exact case-insensitive match from preloaded branches
        if (isset($this->branches[$normalizedName])) {
            Log::info('Branch exact match', ['name' => $normalizedName, 'id' => $this->branches[$normalizedName]]);
            return $this->branches[$normalizedName];
        }

        // Fallback: try partial match
        foreach ($this->branches as $name => $id) {
            if (str_contains($name, $normalizedName) || str_contains($normalizedName, $name)) {
                Log::info('Branch partial match', ['input' => $normalizedName, 'matched' => $name, 'id' => $id]);
                return $id;
            }
        }

        Log::warning('Branch not found', ['input' => $normalizedName, 'available' => array_keys($this->branches)]);
        return $fallbackId;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'employee_id' => 'required|string|max:50',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:100',
            'branch_name' => 'nullable|string',
            'reporting_to_email' => 'nullable|email',
            'date_of_joining' => 'nullable',
            'date_of_birth' => 'required',
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable',
            'password' => 'nullable|string|min:6',
            'roles' => 'nullable|string',
            'can_interview' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email format',
            'date_of_birth.required' => 'Date of birth is required',
        ];
    }

    /**
     * Handle validation failures (SkipsOnFailure)
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'row' => $failure->row(),
                'email' => 'N/A',
                'error' => implode(', ', $failure->errors()),
            ];
            Log::error('Validation failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ]);
        }
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            Log::debug('Empty date value');
            return null;
        }

        Log::debug('Parsing date', ['raw_value' => $value, 'type' => gettype($value)]);

        // Excel numeric date
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                Log::debug('Excel date parsed', ['result' => $date->format('Y-m-d')]);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Failed to parse Excel date', ['value' => $value, 'error' => $e->getMessage()]);
            }
        }

        // String dates
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, (string) $value);
            if ($date && $date->format($format) === (string) $value) {
                Log::debug('String date parsed', ['format' => $format, 'result' => $date->format('Y-m-d')]);
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as last resort
        $timestamp = strtotime((string) $value);
        if ($timestamp !== false) {
            $result = date('Y-m-d', $timestamp);
            Log::debug('strtotime parsed', ['result' => $result]);
            return $result;
        }

        Log::warning('Could not parse date', ['value' => $value]);
        return null;
    }

    private function toBoolean($value): bool
    {
        if (is_bool($value))
            return $value;
        if (is_numeric($value))
            return (bool) $value;
        return in_array(strtolower(trim((string) $value)), ['yes', '1', 'true', 'y']);
    }
}