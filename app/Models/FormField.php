<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_id',
        'parent_field_id',
        'field_name',
        'field_label',
        'field_placeholder',
        'field_type',
        'is_mandatory',
        'is_system',
        'can_have_dependents',
        'order',
        'options',
        'validation_rules',
        'show_when',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_system' => 'boolean',
        'can_have_dependents' => 'boolean',
        'options' => 'json',
        'validation_rules' => 'json',
        'show_when' => 'json',
        'deleted_at' => 'datetime',
    ];

    public const VALIDATION_RULES = [
        'min_length' => ['label' => 'Minimum Length', 'type' => 'number'],
        'max_length' => ['label' => 'Maximum Length', 'type' => 'number'],
        'min_value' => ['label' => 'Minimum Value', 'type' => 'number'],
        'max_value' => ['label' => 'Maximum Value', 'type' => 'number'],
        'min_age' => ['label' => 'Minimum Age', 'type' => 'number'],
        'max_age' => ['label' => 'Maximum Age', 'type' => 'number'],
        'regex' => ['label' => 'Regular Expression', 'type' => 'text'],
        'in' => ['label' => 'Accepted Values', 'type' => 'textarea'],
        'not_in' => ['label' => 'Rejected Values', 'type' => 'textarea'],
        'starts_with' => ['label' => 'Starts With', 'type' => 'text'],
        'ends_with' => ['label' => 'Ends With', 'type' => 'text'],
        'size' => ['label' => 'Exact Size', 'type' => 'number'],
        'date_format' => ['label' => 'Date Format', 'type' => 'text'],
        'before_date' => ['label' => 'Before Date', 'type' => 'text'],
        'after_date' => ['label' => 'After Date', 'type' => 'text'],
        'integer' => ['label' => 'Integer Only', 'type' => 'boolean'],
        'numeric' => ['label' => 'Numeric Only', 'type' => 'boolean'],
        'alpha' => ['label' => 'Alphabetic Only', 'type' => 'boolean'],
        'alpha_num' => ['label' => 'Alphanumeric Only', 'type' => 'boolean'],
        'min_selected' => ['label' => 'Minimum Selected', 'type' => 'number'],
        'max_selected' => ['label' => 'Maximum Selected', 'type' => 'number'],
        'mimes' => ['label' => 'Allowed MIME Types', 'type' => 'text'],
        'max_size' => ['label' => 'Max File Size (KB)', 'type' => 'number'],
    ];

    public const VALIDATION_RULES_BY_FIELD_TYPE = [
        'text' => ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with', 'size', 'alpha', 'alpha_num'],
        'email' => ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with'],
        'phone' => ['min_length', 'max_length', 'regex', 'in', 'not_in', 'numeric'],
        'textarea' => ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with', 'size'],
        'number' => ['min_value', 'max_value', 'in', 'not_in', 'integer', 'numeric', 'size'],
        'date' => ['min_age', 'max_age', 'in', 'not_in', 'date_format', 'before_date', 'after_date'],
        'dropdown' => ['in', 'not_in'],
        'radio' => ['in', 'not_in'],
        'checkbox' => ['min_selected', 'max_selected'],
        'file' => ['mimes', 'max_size'],
    ];

    public static function getAllowedValidationRules(): array
    {
        return self::VALIDATION_RULES;
    }

    public static function getAllowedValidationRulesByFieldType(): array
    {
        return self::VALIDATION_RULES_BY_FIELD_TYPE;
    }

    /* ─── Relations ─── */

    public function form(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'form_id');
    }

    public function parentField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_field_id');
    }

    public function childFields(): HasMany
    {
        return $this->hasMany(self::class, 'parent_field_id')->orderBy('order');
    }

    // Alias for semantic clarity in rendering contexts
    public function dependentFields(): HasMany
    {
        return $this->childFields();
    }

    /* ─── Scopes ─── */

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where(function ($q) {
            $q->where('is_system', false)->orWhereNull('is_system');
        });
    }

    public function scopeIndependent($query)
    {
        return $query->whereNull('parent_field_id');
    }

    public function scopeDependent($query)
    {
        return $query->whereNotNull('parent_field_id');
    }

    /* ─── Helpers ─── */

    public function isDependent(): bool
    {
        return $this->parent_field_id !== null;
    }

    public function scopeCanHaveDependents($query)
    {
        return $query->where('can_have_dependents', true);
    }

    public function isParent(): bool
    {
        return $this->childFields()->exists();
    }

    public function getTriggerOptions(): array
    {
        return $this->options ?? [];
    }

    /**
     * Check if this field should be shown based on parent value.
     */
    public function shouldShow(mixed $parentValue): bool
    {
        if (!$this->show_when || !$this->parent_field_id) {
            return true;
        }

        $triggerValues = array_values((array) ($this->show_when[array_key_first($this->show_when)] ?? []));

        if (is_array($parentValue)) {
            return count(array_intersect($parentValue, $triggerValues)) > 0;
        }

        return in_array($parentValue, $triggerValues, true);
    }

    public function isVisible(array $formData): bool
    {
        if (!$this->parent_field_id) {
            return true;
        }

        $parentField = $this->parentField;
        if (!$parentField) {
            return true;
        }

        $parentValue = $formData[$parentField->field_name] ?? null;
        return $this->shouldShow($parentValue);
    }

    /**
     * Build Laravel validation rules for this field
     */
    /**
     * Build Laravel validation rules for this field
     */
    public function buildValidationRules(array $formData = []): array
    {
        // If field is conditional and not visible, skip validation entirely
        if ($this->parent_field_id && !$this->isVisible($formData)) {
            return ['nullable'];
        }

        $rules = [];

        // Base required/nullable
        if ($this->is_mandatory) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-specific rules — each as separate array element
        match ($this->field_type) {
            'email' => $rules[] = 'email',
            'number' => $rules[] = 'numeric',
            'date' => $rules[] = 'date',
            'file' => array_push($rules, 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'),
            default => null,
        };

        // Dynamic validation_rules from DB
        $customRules = $this->validation_rules ?? [];
        foreach ($customRules as $rule => $params) {
            $laravelRule = $this->mapCustomRule($rule, $params);
            if ($laravelRule) {
                $rules[] = $laravelRule;
            }
        }

        return $rules;
    }

    /**
     * Map custom rule definitions to Laravel validation rules
     */
    private function mapCustomRule(string $rule, mixed $params): ?string
    {
        return match ($rule) {
            'min_age' => 'before_or_equal:' . now()->subYears((int) $params)->format('Y-m-d'),
            'max_age' => 'after_or_equal:' . now()->subYears((int) $params)->format('Y-m-d'),
            'min_length' => 'min:' . (int) $params,
            'max_length' => 'max:' . (int) $params,
            'min_value' => 'min:' . (int) $params,
            'max_value' => 'max:' . (int) $params,
            'size' => 'size:' . (int) $params,
            'min_selected' => 'min:' . (int) $params,
            'max_selected' => 'max:' . (int) $params,
            'integer' => $params ? 'integer' : null,
            'numeric' => $params ? 'numeric' : null,
            'alpha' => $params ? 'alpha' : null,
            'alpha_num' => $params ? 'alpha_num' : null,
            'starts_with' => 'starts_with:' . $params,
            'ends_with' => 'ends_with:' . $params,
            'date_format' => 'date_format:' . $params,
            'before_date' => 'before:' . $params,
            'after_date' => 'after:' . $params,
            'mimes' => 'mimes:' . $params,
            'max_size' => 'max:' . (int) $params,
            'regex' => 'regex:' . $params,
            'in' => 'in:' . (is_array($params) ? implode(',', $params) : $params),
            'not_in' => 'not_in:' . (is_array($params) ? implode(',', $params) : $params),
            default => null,
        };
    }

    /**
     * Get human-readable validation messages
     */
    public function getValidationMessages(): array
    {
        $messages = [];
        $customRules = $this->validation_rules ?? [];

        foreach ($customRules as $rule => $params) {
            $key = $this->field_name . '.' . match ($rule) {
                'min_age' => 'before_or_equal',
                'max_age' => 'after_or_equal',
                default => $rule,
            };

            $messages[$key] = match ($rule) {
                'min_age' => "You must be at least {$params} years old.",
                'max_age' => "You must be at most {$params} years old.",
                'min_length' => "{$this->field_label} must be at least {$params} characters.",
                'max_length' => "{$this->field_label} must be at most {$params} characters.",
                'min_value' => "{$this->field_label} must be at least {$params}.",
                'max_value' => "{$this->field_label} must be at most {$params}.",
                'size' => "{$this->field_label} must be exactly {$params} characters.",
                'min_selected' => "Select at least {$params} options for {$this->field_label}.",
                'max_selected' => "Select no more than {$params} options for {$this->field_label}.",
                'starts_with' => "{$this->field_label} must start with {$params}.",
                'ends_with' => "{$this->field_label} must end with {$params}.",
                'date_format' => "{$this->field_label} must match the date format {$params}.",
                'before_date' => "{$this->field_label} must be a date before {$params}.",
                'after_date' => "{$this->field_label} must be a date after {$params}.",
                'mimes' => "{$this->field_label} must be a file of type: {$params}.",
                'max_size' => "{$this->field_label} must be no larger than {$params} kilobytes.",
                default => null,
            };
        }

        return array_filter($messages);
    }
}