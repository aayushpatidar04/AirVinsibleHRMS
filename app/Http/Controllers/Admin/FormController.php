<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FormField;
use App\Models\RegistrationForm;
use App\Traits\HasSystemFields;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FormController extends Controller
{
    const FIELD_TYPES = [
        'text' => 'Text',
        'email' => 'Email',
        'phone' => 'Phone',
        'number' => 'Number',
        'date' => 'Date',
        'textarea' => 'Textarea',
        'dropdown' => 'Dropdown',
        'radio' => 'Radio',
        'checkbox' => 'Checkbox',
        'file' => 'File Upload',
    ];

    public function index(Request $request)
    {
        $forms = RegistrationForm::with('branch')
            ->withCount('fields', 'submissions')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->branch_id, fn($q, $b) => $q->where('branch_id', $b)->orWhereNull('branch_id'))
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Forms/Index', [
            'forms' => $forms,
            'branches' => Branch::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'branch_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Forms/CreateEdit', [
            'form' => null,
            'fields' => [],
            'branches' => Branch::active()->get(['id', 'name']),
            'fieldTypes' => self::FIELD_TYPES,
            'systemFields' => RegistrationForm::getSystemFields(),  // ← ADD
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'fields' => 'required|array|min:0',
            'fields.*.field_label' => 'required|string|max:255',
            'fields.*.field_name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'fields.*.field_type' => 'required|in:text,email,phone,number,date,textarea,dropdown,radio,checkbox,file',
            'fields.*.is_mandatory' => 'boolean',
            'fields.*.is_system' => 'boolean',
            'fields.*.can_have_dependents' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.field_placeholder' => 'nullable|string',
            'fields.*.show_when' => 'nullable|array',
            'fields.*.parent_field_id' => 'nullable|integer|exists:form_fields,id',
            'fields.*.validation_rules' => 'nullable|array',
        ]);

        // Prevent system field names in custom fields (unless explicitly marked system)
        $systemNames = RegistrationForm::getSystemFieldNames();
        foreach ($validated['fields'] as $field) {
            if (!$field['is_system'] && in_array($field['field_name'], $systemNames)) {
                return back()->withErrors(['fields' => "Field name '{$field['field_name']}' is reserved for system fields."]);
            }
        }

        $this->validateFieldValidationRules($validated['fields']);

        $form = RegistrationForm::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        // System fields auto-created via booted() event

        // Create custom fields (including ones marked as system manually)
        foreach ($validated['fields'] as $index => $fieldData) {
            $form->fields()->create([
                'field_name' => $fieldData['field_name'],
                'field_label' => $fieldData['field_label'],
                'field_type' => $fieldData['field_type'],
                'field_placeholder' => $fieldData['field_placeholder'] ?? '',
                'is_mandatory' => $fieldData['is_mandatory'] ?? false,
                'is_system' => $fieldData['is_system'] ?? false,
                'order' => count($systemNames) + $index + 1,
                'options' => $fieldData['options'] ?? null,
                'validation_rules' => $fieldData['validation_rules'] ?? null,
                'show_when' => $fieldData['show_when'] ?? null,
                'parent_field_id' => $fieldData['parent_field_id'] ?? null,
                'can_have_dependents' => $fieldData['can_have_dependents'] ?? null,
            ]);
        }

        return redirect()->route('admin.forms.index')->with('success', 'Form created successfully.');
    }

    public function show(RegistrationForm $form)
    {
        return Inertia::render('Admin/Forms/Show', [
            'form' => array_merge($form->toArray(), [
                'branch_name' => $form->branch?->name ?? 'Global',
                'total_submissions' => $form->submissions()->count(),
            ]),
            'fields' => $form->fields()->get(),
            'fieldTypes' => self::FIELD_TYPES,
            'qrCodes' => $form->qrCodes()->with('branch')->active()->get()->map(fn($q) => [
                'id' => $q->id,
                'label' => $q->label,
                'uuid' => $q->uuid,
                'branch' => $q->branch->name,
                'usage_count' => $q->usage_count,
                'expiry' => $q->expiry_date?->format('d M Y'),
                'is_active' => $q->is_active,
            ]),
        ]);
    }

    public function edit(RegistrationForm $form)
    {
        $form->load('fields');

        return Inertia::render('Admin/Forms/CreateEdit', [
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'branch_id' => $form->branch_id,
                'description' => $form->description,
                'is_active' => $form->is_active,
            ],
            'fields' => $form->customFields()->get()->map(fn($f) => [
                'id' => $f->id,
                'field_name' => $f->field_name,
                'field_label' => $f->field_label,
                'field_type' => $f->field_type,
                'field_placeholder' => $f->field_placeholder,
                'is_mandatory' => $f->is_mandatory,
                'is_system' => $f->is_system,
                'order' => $f->order,
                'options' => $f->options ? (is_array($f->options) ? $f->options : array_values((array) $f->options)) : [],
                'validation_rules' => $f->validation_rules ?? null,
            ]),
            'branches' => Branch::active()->get(['id', 'name']),
            'fieldTypes' => self::FIELD_TYPES,
            'systemFields' => RegistrationForm::getSystemFields(),  // ← ADD
        ]);
    }

    public function update(Request $request, RegistrationForm $form)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'fields' => 'required|array',
            'fields.*.id' => 'nullable|exists:form_fields,id',
            'fields.*.field_label' => 'required|string|max:255',
            'fields.*.field_name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'fields.*.field_type' => 'required|in:text,email,phone,number,date,textarea,dropdown,radio,checkbox,file',
            'fields.*.is_mandatory' => 'boolean',
            'fields.*.is_system' => 'boolean',
            'fields.*.can_have_dependents' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.field_placeholder' => 'nullable|string',
            'fields.*.show_when' => 'nullable|array',
            'fields.*.parent_field_id' => 'nullable|integer|exists:form_fields,id',
            'fields.*.validation_rules' => 'nullable|array',
        ]);

        $this->validateFieldValidationRules($validated['fields']);

        $form->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $systemNames = RegistrationForm::getSystemFieldNames();
        $existingCustom = $form->customFields()->pluck('id')->toArray();
        $submittedIds = collect($validated['fields'])->pluck('id')->filter()->toArray();

        // Delete removed custom fields
        $toDelete = array_diff($existingCustom, $submittedIds);
        $form->fields()->whereIn('id', $toDelete)->delete();

        // Update or create custom fields
        foreach ($validated['fields'] as $index => $fieldData) {
            // Skip if trying to use system name on non-system field
            if (!$fieldData['is_system'] && in_array($fieldData['field_name'], $systemNames)) {
                continue;
            }

            $systemCount = $form->systemFields()->count();
            $data = [
                'field_name' => $fieldData['field_name'],
                'field_label' => $fieldData['field_label'],
                'field_type' => $fieldData['field_type'],
                'field_placeholder' => $fieldData['field_placeholder'] ?? '',
                'is_mandatory' => $fieldData['is_mandatory'] ?? false,
                'is_system' => $fieldData['is_system'] ?? false,
                'order' => $systemCount + $index + 1,
                'options' => $fieldData['options'] ?? null,
                'validation_rules' => $fieldData['validation_rules'] ?? null,
                'show_when' => $fieldData['show_when'] ?? null,
                'parent_field_id' => $fieldData['parent_field_id'] ?? null,
                'can_have_dependents' => $fieldData['can_have_dependents'] ?? null,
            ];

            if (!empty($fieldData['id'])) {
                $form->fields()->where('id', $fieldData['id'])->update($data);
            } else {
                $form->fields()->create($data);
            }
        }

        return redirect()->route('admin.forms.index')->with('success', 'Form updated successfully.');
    }

    private function validateFieldValidationRules(array $fields): void
    {
        $allowedRules = FormField::getAllowedValidationRules();
        $allowedByType = FormField::getAllowedValidationRulesByFieldType();

        foreach ($fields as $index => $field) {
            $fieldType = $field['field_type'] ?? '';
            $allowedForType = $allowedByType[$fieldType] ?? [];
            $rules = $field['validation_rules'] ?? [];

            if (!is_array($rules)) {
                throw ValidationException::withMessages([
                    "fields.$index.validation_rules" => 'Invalid validation rule format.',
                ]);
            }

            foreach ($rules as $ruleKey => $ruleValue) {
                if (!isset($allowedRules[$ruleKey])) {
                    throw ValidationException::withMessages([
                        "fields.$index.validation_rules.$ruleKey" => "The validation rule '{$ruleKey}' is not supported.",
                    ]);
                }

                if (!in_array($ruleKey, $allowedForType, true)) {
                    throw ValidationException::withMessages([
                        "fields.$index.validation_rules.$ruleKey" => "The validation rule '{$allowedRules[$ruleKey]['label']}' is not allowed for field type '{$fieldType}'.",
                    ]);
                }

                if ($ruleValue === '' || $ruleValue === null) {
                    throw ValidationException::withMessages([
                        "fields.$index.validation_rules.$ruleKey" => "The '{$allowedRules[$ruleKey]['label']}' value is required.",
                    ]);
                }

                if (in_array($ruleKey, ['min_length', 'max_length', 'min_value', 'max_value', 'min_age', 'max_age'], true)) {
                    if (!ctype_digit((string) $ruleValue) || (int) $ruleValue < 0) {
                        throw ValidationException::withMessages([
                            "fields.$index.validation_rules.$ruleKey" => "The '{$allowedRules[$ruleKey]['label']}' must be a whole number.",
                        ]);
                    }
                }

                if (in_array($ruleKey, ['in', 'not_in'], true)) {
                    $values = array_filter(array_map('trim', explode(',', (string) $ruleValue)), fn ($value) => $value !== '');
                    if (empty($values)) {
                        throw ValidationException::withMessages([
                            "fields.$index.validation_rules.$ruleKey" => "The '{$allowedRules[$ruleKey]['label']}' must contain at least one value.",
                        ]);
                    }
                }

                if ($ruleKey === 'regex') {
                    if (!$this->isValidRegexPattern($ruleValue)) {
                        throw ValidationException::withMessages([
                            "fields.$index.validation_rules.$ruleKey" => "The '{$allowedRules[$ruleKey]['label']}' pattern is invalid.",
                        ]);
                    }
                }
            }
        }
    }

    private function isValidRegexPattern(string $pattern): bool
    {
        if (@preg_match($pattern, '') !== false) {
            return true;
        }

        if (str_starts_with($pattern, '/') && str_ends_with($pattern, '/')) {
            return @preg_match($pattern, '') !== false;
        }

        $wrapped = '/' . str_replace('/', '\/', $pattern) . '/';
        return @preg_match($wrapped, '') !== false;
    }

    public function destroy(RegistrationForm $form)
    {
        $form->delete();
        return redirect()->route('admin.forms.index')->with('success', 'Form deleted.');
    }
}