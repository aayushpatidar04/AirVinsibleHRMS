<?php

namespace App\Models;

use App\Traits\HasSystemFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class RegistrationForm extends Model
{
    use HasFactory, SoftDeletes, HasSystemFields;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'version',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /* ─── Boot ─── */

    protected static function booted()
    {
        static::created(function ($form) {
            $form->createSystemFields();
        });
    }

    /* ─── System Fields Creation ─── */

    public function createSystemFields(): void
    {
        $systemFields = self::getSystemFields();
        $createdFields = [];

        // ── First pass: create all fields without parent/show_when dependencies
        foreach ($systemFields as $fieldData) {
            if ($this->fields()->where('field_name', $fieldData['field_name'])->exists()) {
                continue;
            }

            // Strip relational data for initial creation
            $createData = array_diff_key($fieldData, [
                'parent_field_id' => 1,
                'show_when' => 1,
            ]);

            $field = $this->fields()->create($createData);
            $createdFields[$fieldData['field_name']] = $field;
        }

        // ── Second pass: link show_when conditional visibility
        foreach ($systemFields as $fieldData) {
            if (empty($fieldData['show_when'])) {
                continue;
            }

            $field = $this->fields()
                ->where('field_name', $fieldData['field_name'])
                ->first();

            if (!$field) {
                continue;
            }

            // show_when key = parent field name, value = trigger options
            $parentFieldName = array_key_first($fieldData['show_when']);
            $parentField = $createdFields[$parentFieldName]
                ?? $this->fields()->where('field_name', $parentFieldName)->first();

            if ($parentField) {
                $field->update([
                    'parent_field_id' => $parentField->id,
                    'show_when' => $fieldData['show_when'],
                ]);
            }
        }

        // ── Third pass: link explicit parent_field_id hierarchies
        foreach ($systemFields as $fieldData) {
            if (empty($fieldData['parent_field_id'])) {
                continue;
            }

            $field = $this->fields()
                ->where('field_name', $fieldData['field_name'])
                ->first();

            if (!$field || $field->parent_field_id !== null) {
                continue; // Skip if already linked via show_when
            }

            $parentFieldName = $fieldData['parent_field_id'];
            $parentField = $createdFields[$parentFieldName]
                ?? $this->fields()->where('field_name', $parentFieldName)->first();

            if ($parentField) {
                $field->update(['parent_field_id' => $parentField->id]);
            }
        }
    }

    public function syncSystemFields(): void
    {
        $this->createSystemFields();

        // Ensure order & labels match the canonical definition
        foreach (self::getSystemFields() as $fieldData) {
            $this->fields()
                ->where('field_name', $fieldData['field_name'])
                ->update([
                    'order' => $fieldData['order'],
                    'field_label' => $fieldData['field_label'],
                    'field_placeholder' => $fieldData['field_placeholder'] ?? null,
                    'is_mandatory' => $fieldData['is_mandatory'],
                ]);
        }
    }

    /* ─── Relations ─── */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')->orderBy('order');
    }

    public function systemFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')
            ->where('is_system', true)
            ->orderBy('order');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')
            ->where(function ($q) {
                $q->where('is_system', false)->orWhereNull('is_system');
            })
            ->orderBy('order');
    }

    public function independentFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')
            ->whereNull('parent_field_id')
            ->orderBy('order');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'form_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CandidateFormSubmission::class, 'form_id');
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        });
    }

    /* ─── Field Rendering ─── */

    /**
     * Get all fields (system + custom) ordered for form rendering.
     * Dependent fields are nested under their parents.
     */
    public function fieldsForRendering(): Collection
    {
        $all = $this->fields()->with('childFields')->get();
        $topLevel = $all->whereNull('parent_field_id')->sortBy('order')->values();

        $result = collect();
        foreach ($topLevel as $field) {
            $result->push($field);
            foreach ($field->childFields->sortBy('order') as $child) {
                $result->push($child);
            }
        }

        return $result;
    }

    /**
     * Get flat ordered list of all fields (top-level + dependent inline).
     */
    public function allFieldsOrdered(): Collection
    {
        return $this->fields()->orderBy('order')->get();
    }
}