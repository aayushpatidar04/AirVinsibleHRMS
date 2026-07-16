<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  form: Object,
  fields: Array,
  branches: Array,
  fieldTypes: Object,
  systemFields: Array,
})

const isEdit = computed(() => !!props.form?.id)

const validationRuleDefinitions = {
  min_length: { label: 'Minimum Length', type: 'number', placeholder: 'e.g. 3' },
  max_length: { label: 'Maximum Length', type: 'number', placeholder: 'e.g. 255' },
  min_value: { label: 'Minimum Value', type: 'number', placeholder: 'e.g. 10' },
  max_value: { label: 'Maximum Value', type: 'number', placeholder: 'e.g. 100' },
  min_age: { label: 'Minimum Age', type: 'number', placeholder: 'e.g. 18' },
  max_age: { label: 'Maximum Age', type: 'number', placeholder: 'e.g. 50' },
  regex: { label: 'Regular Expression', type: 'text', placeholder: 'e.g. ^[A-Za-z ]+$' },
  in: { label: 'Accepted Values', type: 'textarea', placeholder: 'Comma-separated values' },
  not_in: { label: 'Rejected Values', type: 'textarea', placeholder: 'Comma-separated values' },
  starts_with: { label: 'Starts With', type: 'text', placeholder: 'e.g. ABC' },
  ends_with: { label: 'Ends With', type: 'text', placeholder: 'e.g. XYZ' },
  size: { label: 'Exact Size', type: 'number', placeholder: 'e.g. 5' },
  date_format: { label: 'Date Format', type: 'text', placeholder: 'e.g. Y-m-d' },
  before_date: { label: 'Before Date', type: 'text', placeholder: 'e.g. 2025-01-01' },
  after_date: { label: 'After Date', type: 'text', placeholder: 'e.g. 2023-01-01' },
  integer: { label: 'Integer Only', type: 'boolean' },
  numeric: { label: 'Numeric Only', type: 'boolean' },
  alpha: { label: 'Alphabetic Only', type: 'boolean' },
  alpha_num: { label: 'Alphanumeric Only', type: 'boolean' },
  min_selected: { label: 'Minimum Selected', type: 'number', placeholder: 'e.g. 1' },
  max_selected: { label: 'Maximum Selected', type: 'number', placeholder: 'e.g. 3' },
  mimes: { label: 'Allowed MIME Types', type: 'text', placeholder: 'e.g. pdf,doc,docx,jpg,png' },
  max_size: { label: 'Max File Size (KB)', type: 'number', placeholder: 'e.g. 5120' },
}

const availableValidationRulesByType = {
  text: ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with', 'size', 'alpha', 'alpha_num'],
  email: ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with'],
  phone: ['min_length', 'max_length', 'regex', 'in', 'not_in', 'numeric'],
  textarea: ['min_length', 'max_length', 'regex', 'in', 'not_in', 'starts_with', 'ends_with', 'size'],
  number: ['min_value', 'max_value', 'in', 'not_in', 'integer', 'numeric', 'size'],
  date: ['min_age', 'max_age', 'in', 'not_in', 'date_format', 'before_date', 'after_date'],
  dropdown: ['in', 'not_in'],
  radio: ['in', 'not_in'],
  checkbox: ['min_selected', 'max_selected'],
  file: ['mimes', 'max_size'],
}

const formData = useForm({
  name: props.form?.name ?? '',
  branch_id: props.form?.branch_id ?? '',
  description: props.form?.description ?? '',
  is_active: props.form?.is_active ?? true,
  fields: (props.fields ?? []).map(f => ({
    id: f.id ?? null,
    field_name: f.field_name,
    field_label: f.field_label,
    field_type: f.field_type,
    field_placeholder: f.field_placeholder ?? '',
    is_mandatory: Boolean(f.is_mandatory),
    is_system: Boolean(f.is_system),
    can_have_dependents: Boolean(f.can_have_dependents),
    order: f.order,
    options: f.options ? (Array.isArray(f.options) ? f.options : Object.values(f.options)) : [],
    show_when: f.show_when ?? null,
    parent_field_id: f.parent_field_id ?? null,
    validation_rules: f.validation_rules ?? {},
    __newValidationRule: '',
  })),
})

// ─── Helpers ───

// Get real index in formData.fields for a custom field object
const getRealIndex = (field) => formData.fields.findIndex(f => f === field)

// All fields (system + custom) for conditional parent lookup
const allFields = computed(() => [...props.systemFields, ...formData.fields])

// Eligible parent fields: must be BEFORE current field in order, have options, and be marked can_have_dependents
const getEligibleParentFields = (currentField) => {
  const currentOrder = currentField.order
  return allFields.value.filter(f =>
    f.order < currentOrder &&
    f.can_have_dependents &&
    ['dropdown', 'radio', 'checkbox'].includes(f.field_type) &&
    f.options?.length > 0
  )
}

// Check if a SAVED field is a parent of any other field
const isParentField = (fieldId) => {
  if (!fieldId) return false
  return formData.fields.some(f => f.parent_field_id === fieldId)
}

// Check if any field depends on this one (for delete protection)
const hasDependents = (field) => {
  if (!field?.id) return false
  return formData.fields.some(f => f.parent_field_id === field.id)
}

// ─── Conditional Logic ───

const toggleConditional = (field) => {
  if (field.show_when) {
    field.show_when = null
    field.parent_field_id = null
  } else {
    const parents = getEligibleParentFields(field)
    if (parents.length === 0) return
    const parent = parents[0]
    field.show_when = { [parent.field_name]: [] }
    field.parent_field_id = parent.id
  }
}

const updateConditionalParent = (field, parentField) => {
  field.show_when = { [parentField.field_name]: [] }
  field.parent_field_id = parentField.id
}

const toggleTriggerOption = (field, option) => {
  const parentName = Object.keys(field.show_when)[0]
  const current = field.show_when[parentName]
  if (current.includes(option)) {
    field.show_when[parentName] = current.filter(o => o !== option)
  } else {
    field.show_when[parentName].push(option)
  }
}

// ─── Form Submit ───

const submit = () => {
  const payload = {
    ...formData.data(),
    fields: formData.fields.map(f => ({
      id: f.id,
      field_name: f.field_name,
      field_label: f.field_label,
      field_type: f.field_type,
      field_placeholder: f.field_placeholder,
      is_mandatory: f.is_mandatory,
      is_system: f.is_system,
      can_have_dependents: f.can_have_dependents,
      order: f.order,
      options: f.options,
      validation_rules: f.validation_rules ?? {},
      show_when: f.show_when,
      parent_field_id: f.parent_field_id,
    })),
  }

  if (isEdit.value) {
    formData.put(route('admin.forms.update', props.form.id), payload)
  } else {
    formData.post(route('admin.forms.store'), payload)
  }
}

const addField = () => {
  const maxOrder = Math.max(
    ...props.systemFields.map(f => f.order),
    ...formData.fields.map(f => f.order),
    0
  )

  formData.fields.push({
    id: null,
    field_name: '',
    field_label: '',
    field_type: 'text',
    field_placeholder: '',
    is_mandatory: false,
    is_system: false,
    can_have_dependents: false,
    order: maxOrder + 1,
    options: [],
    show_when: null,
    parent_field_id: null,
    validation_rules: {},
    __newValidationRule: '',
  })
}

const removeField = (field) => {
  const idx = getRealIndex(field)
  if (idx === -1) return

  if (field.is_system) {
    console.log('Cannot remove system field')
    return
  }

  if (hasDependents(field)) {
    const dependent = formData.fields.find(f => f.parent_field_id === field.id)
    alert(`Cannot remove: "${dependent?.field_label}" depends on this field. Remove the dependent field first.`)
    return
  }

  // Clear dependencies pointing to this field
  formData.fields.forEach(f => {
    if (f.parent_field_id === field.id) {
      f.parent_field_id = null
      f.show_when = null
    }
  })

  formData.fields.splice(idx, 1)
  recalcOrders()
}

const moveField = (field, direction) => {
  const idx = getRealIndex(field)
  if (idx === -1) return

  const swapIdx = direction === 'up' ? idx - 1 : idx + 1
  if (swapIdx < 0 || swapIdx >= formData.fields.length) return
  if (formData.fields[swapIdx].is_system) return

  const t = formData.fields[idx]
  formData.fields[idx] = formData.fields[swapIdx]
  formData.fields[swapIdx] = t

  recalcOrders()
}

const recalcOrders = () => {
  const systemCount = props.systemFields.length
  // Custom fields get orders after system fields
  formData.fields.forEach((f, idx) => {
    f.order = systemCount + idx + 1
  })
}

const needsOptions = (type) => ['dropdown', 'radio', 'checkbox', 'multiple_choice'].includes(type)

const getAllowedValidationRules = (field) => {
  return availableValidationRulesByType[field.field_type] ?? []
}

const getAvailableValidationRules = (field) => {
  const allowed = getAllowedValidationRules(field)
  return allowed.filter(rule => !(field.validation_rules && Object.prototype.hasOwnProperty.call(field.validation_rules, rule)))
}

const addValidationRule = (field, ruleKey) => {
  if (!ruleKey) return
  if (!field.validation_rules) {
    field.validation_rules = {}
  }
  if (!Object.prototype.hasOwnProperty.call(field.validation_rules, ruleKey)) {
    field.validation_rules[ruleKey] = validationRuleDefinitions[ruleKey]?.type === 'boolean' ? true : ''
  }
  field.__newValidationRule = ''
}

const removeValidationRule = (field, ruleKey) => {
  if (!field.validation_rules) return
  delete field.validation_rules[ruleKey]
}

const ensureValidationForType = (field) => {
  if (!field.validation_rules) return
  const allowed = getAllowedValidationRules(field)
  Object.keys(field.validation_rules).forEach(ruleKey => {
    if (!allowed.includes(ruleKey)) {
      delete field.validation_rules[ruleKey]
    }
  })
  field.__newValidationRule = ''
}

const autoFieldName = (field) => {
  if (!field.field_name && !field.is_system) {
    const base = field.field_label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+$/, '')
    const systemNames = props.systemFields.map(f => f.field_name)
    const usedNames = formData.fields.map(f => f.field_name).filter(n => n)

    let name = systemNames.includes(base) ? base + '_custom' : base
    let counter = 1
    while (usedNames.includes(name)) {
      name = `${base}_${counter++}`
    }
    field.field_name = name
  }
}

const optionInputs = ref({})

const addOption = (field, inputKey) => {
  const val = optionInputs.value[inputKey]?.trim()
  if (val && !field.options.includes(val)) {
    field.options.push(val)
    optionInputs.value[inputKey] = ''
  }
}

const removeOption = (field, optIndex) => {
  field.options.splice(optIndex, 1)
}

// ─── Computed ───

const systemFields = computed(() => props.systemFields.map((f, idx) => ({
  ...f,
  order: idx + 1,
  is_system: true,
  can_have_dependents: f.can_have_dependents ?? true,
})))

const customFields = computed(() => formData.fields)

const isReservedName = (name) => {
  if (!name) return false
  return props.systemFields.map(f => f.field_name).includes(name)
}
</script>

<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-5">
      <div class="flex items-center gap-3">
        <Link :href="isEdit ? route('admin.forms.show', form.id) : route('admin.forms.index')"
          class="text-gray-400 hover:text-gray-600">←</Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Form' : 'New Registration Form' }}</h1>
          <p class="text-sm text-gray-500">Build your form with conditional fields</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
          <h2 class="font-semibold text-gray-800">Form Details</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Form Name <span class="text-red-500">*</span></label>
              <input v-model="formData.name" type="text" class="input-field"
                placeholder="e.g. Standard Walk-In Registration" />
              <p v-if="formData.errors.name" class="error">{{ formData.errors.name }}</p>
            </div>
            <div>
              <label class="label">Branch <span class="text-gray-400 text-xs">(leave blank for global)</span></label>
              <select v-model="formData.branch_id" class="input-field">
                <option value="">Global (all branches)</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="label">Description</label>
            <textarea v-model="formData.description" rows="2" class="input-field"
              placeholder="Brief description shown to candidates..." />
          </div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="formData.is_active" type="checkbox" class="rounded text-indigo-600" />
            <span class="text-sm text-gray-700">Form is active</span>
          </label>
        </div>

        <!-- System Fields -->
        <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-blue-200 bg-blue-100 flex items-center justify-between">
            <h2 class="font-semibold text-blue-900 flex items-center gap-2">
              🔒 System Fields
              <span class="text-xs font-normal text-blue-700">(Auto-included, cannot modify)</span>
            </h2>
            <span class="text-xs bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full">
              {{ systemFields.length }} fields
            </span>
          </div>
          <div class="divide-y divide-blue-100">
            <div v-for="field in systemFields" :key="field.field_name" class="p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-blue-800 flex items-center gap-2">
                  <span class="text-xs bg-blue-200 text-blue-800 px-1.5 py-0.5 rounded">#{{ field.order }}</span>
                  {{ field.field_label }}
                  <span v-if="field.is_mandatory"
                    class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Required</span>
                  <span v-if="field.show_when"
                    class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Conditional</span>
                </span>
                <span class="text-xs text-blue-600 font-mono bg-blue-100 px-2 py-0.5 rounded">{{ field.field_name
                  }}</span>
              </div>
              <div class="grid grid-cols-3 gap-2 text-xs text-blue-700">
                <div>Type: <span class="font-medium">{{ fieldTypes[field.field_type] ?? field.field_type }}</span></div>
                <div>Placeholder: <span class="font-medium">{{ field.field_placeholder || '—' }}</span></div>
                <div v-if="field.options?.length">Options: <span class="font-medium">{{ field.options.join(', ')
                    }}</span></div>
              </div>
              <div v-if="field.show_when" class="mt-2 text-xs text-amber-700 bg-amber-50 rounded px-2 py-1">
                📎 Shows when <strong>{{ Object.keys(field.show_when)[0] }}</strong> is
                <strong>{{ Object.values(field.show_when)[0].join(' or ') }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Custom Fields Builder -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
              Custom Fields
              <span class="text-xs font-normal text-gray-500">(Configure fields, set conditions)</span>
            </h2>
            <button type="button" @click="addField" class="btn-primary text-xs">+ Add Field</button>
          </div>

          <div class="divide-y divide-gray-50">
            <div v-for="(field, i) in customFields" :key="field.field_name + '-' + i" class="p-5 space-y-4"
              :class="{ 'bg-amber-50/30': field.show_when, 'bg-indigo-50/30': field.can_have_dependents }">

              <!-- Field Header -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-sm font-semibold text-gray-500">Field #{{ systemFields.length + i + 1 }}</span>

                  <span v-if="field.is_system"
                    class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">System</span>
                  <span v-if="field.show_when"
                    class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Conditional
                  </span>
                  <span v-if="field.can_have_dependents"
                    class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">
                    Can Have Dependents
                  </span>
                  <span v-if="isParentField(field.id)"
                    class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                    Has {{formData.fields.filter(f => f.parent_field_id === field.id).length}} dependent(s)
                  </span>
                </div>
                <div class="flex gap-2">
                  <button type="button" @click="moveField(field, 'up')" :disabled="i === 0"
                    class="text-gray-300 hover:text-gray-600 disabled:opacity-30 text-sm px-1">▲</button>
                  <button type="button" @click="moveField(field, 'down')" :disabled="i >= customFields.length - 1"
                    class="text-gray-300 hover:text-gray-600 disabled:opacity-30 text-sm px-1">▼</button>
                  <button type="button" @click="removeField(field)"
                    class="text-red-400 hover:text-red-600 text-xs px-2">Remove</button>
                </div>
              </div>

              <!-- Basic Field Info -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label text-xs">Label <span class="text-red-500">*</span></label>
                  <input v-model="field.field_label" type="text" class="input-field" placeholder="e.g. Full Name"
                    @blur="autoFieldName(field)" />
                </div>
                <div>
                  <label class="label text-xs">Field Name <span class="text-xs text-gray-400">(auto)</span></label>
                  <input v-model="field.field_name" type="text" class="input-field font-mono text-xs"
                    :class="{ 'border-red-300 bg-red-50': isReservedName(field.field_name) }"
                    placeholder="e.g. full_name" />
                  <p v-if="isReservedName(field.field_name)" class="text-xs text-red-500 mt-1">
                    ⚠️ Reserved name. Use a different name.
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label text-xs">Type <span class="text-red-500">*</span></label>
                  <select v-model="field.field_type" @change="ensureValidationForType(field)" class="input-field">
                    <option v-for="(l, k) in fieldTypes" :key="k" :value="k">{{ l }}</option>
                  </select>
                </div>
                <div>
                  <label class="label text-xs">Placeholder</label>
                  <input v-model="field.field_placeholder" type="text" class="input-field"
                    placeholder="Hint text for candidate" />
                </div>
              </div>

              <!-- Field Flags -->
              <div class="flex flex-wrap gap-4 py-2 border-y border-gray-100">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="field.is_mandatory" type="checkbox" class="rounded text-red-500" />
                  <span class="text-xs text-gray-700 font-medium">Required</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="field.is_system" type="checkbox" class="rounded text-blue-500" />
                  <span class="text-xs text-gray-700 font-medium">System Field</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer"
                  :class="{ 'opacity-50': !needsOptions(field.field_type) }">
                  <input v-model="field.can_have_dependents" type="checkbox" class="rounded text-indigo-500"
                    :disabled="!needsOptions(field.field_type)" />
                  <span class="text-xs text-gray-700 font-medium">Can Have Dependents</span>
                  <span v-if="!needsOptions(field.field_type)" class="text-[10px] text-gray-400">(needs
                    dropdown/radio/checkbox)</span>
                </label>
              </div>

              <!-- Options for dropdown/radio/checkbox -->
              <div v-if="needsOptions(field.field_type)">
                <label class="label text-xs">Options</label>
                <div class="flex gap-2 mb-2">
                  <input v-model="optionInputs[field.field_name]" type="text" class="input-field flex-1"
                    placeholder="Add option" @keydown.enter.prevent="addOption(field, field.field_name)" />
                  <button type="button" @click="addOption(field, field.field_name)"
                    class="btn-secondary text-xs">Add</button>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="(opt, oi) in field.options" :key="oi"
                    class="flex items-center gap-1 bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                    {{ opt }}
                    <button type="button" @click="removeOption(field, oi)"
                      class="text-red-400 hover:text-red-600 text-base leading-none ml-1">×</button>
                  </span>
                  <span v-if="!field.options?.length" class="text-xs text-gray-400 italic">No options yet</span>
                </div>
              </div>

              <!-- Validation Rules -->
              <div class="border border-gray-200 rounded-lg p-3 bg-slate-50">
                <div class="flex items-center justify-between mb-3">
                  <label class="text-xs font-semibold text-gray-700">Validation Rules</label>
                  <span class="text-[11px] text-gray-500">Select from allowed rules only</span>
                </div>

                <div class="space-y-3">
                  <div v-if="Object.keys(field.validation_rules || {}).length">
                    <div v-for="(value, ruleKey) in field.validation_rules" :key="ruleKey"
                      class="grid gap-2 sm:grid-cols-[1fr_auto] items-end">
                      <div>
                        <div class="flex items-center justify-between mb-1">
                          <span class="text-xs font-medium text-gray-700">{{ validationRuleDefinitions[ruleKey]?.label || ruleKey }}</span>
                          <button type="button" @click="removeValidationRule(field, ruleKey)"
                            class="text-red-500 text-xs hover:text-red-700">Remove</button>
                        </div>
                        <div>
                          <template v-if="validationRuleDefinitions[ruleKey]?.type === 'textarea'">
                            <textarea
                              v-model="field.validation_rules[ruleKey]"
                              :placeholder="validationRuleDefinitions[ruleKey]?.placeholder"
                              rows="2"
                              class="input-field text-xs"></textarea>
                          </template>
                          <template v-else-if="validationRuleDefinitions[ruleKey]?.type === 'boolean'">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                              <input type="checkbox" class="rounded text-indigo-600" v-model="field.validation_rules[ruleKey]" />
                              Enabled
                            </label>
                          </template>
                          <template v-else>
                            <input
                              v-model="field.validation_rules[ruleKey]"
                              :type="validationRuleDefinitions[ruleKey]?.type || 'text'"
                              :placeholder="validationRuleDefinitions[ruleKey]?.placeholder"
                              class="input-field text-xs" />
                          </template>
                        </div>
                        <p v-if="['in', 'not_in'].includes(ruleKey)" class="text-[11px] text-gray-500 mt-1">
                          Use comma-separated values, e.g. <span class="font-mono">option1,option2</span>
                        </p>
                        <p v-if="ruleKey === 'regex'" class="text-[11px] text-gray-500 mt-1">
                          Enter a valid regex pattern, without surrounding slashes unless you want them.
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-wrap gap-2 items-center">
                    <select v-model="field.__newValidationRule" class="input-field flex-1 text-xs">
                      <option value="" disabled>Choose rule</option>
                      <option v-for="rule in getAvailableValidationRules(field)" :key="rule" :value="rule">
                        {{ validationRuleDefinitions[rule]?.label || rule }}
                      </option>
                    </select>
                    <button type="button" @click="addValidationRule(field, field.__newValidationRule)"
                      class="btn-secondary text-xs">Add Rule</button>
                  </div>

                  <p v-if="getAvailableValidationRules(field).length === 0"
                    class="text-xs text-gray-500 italic">
                    No additional validation rules available for {{ fieldTypes[field.field_type] }}.
                  </p>
                </div>
              </div>

              <!-- Conditional Visibility Section -->
              <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                <div class="flex items-center justify-between mb-2">
                  <label class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Conditional Visibility
                  </label>
                  <button v-if="!field.show_when" type="button" @click="toggleConditional(field)"
                    :disabled="getEligibleParentFields(field).length === 0"
                    class="text-xs text-indigo-600 hover:text-indigo-800 disabled:text-gray-400 disabled:cursor-not-allowed">
                    + Add Condition
                  </button>
                  <button v-else type="button" @click="toggleConditional(field)"
                    class="text-xs text-red-500 hover:text-red-700">
                    Remove Condition
                  </button>
                </div>

                <div v-if="field.show_when" class="space-y-3">
                  <div>
                    <label class="text-xs text-gray-600 mb-1 block">Show this field when:</label>
                    <select @change="updateConditionalParent(field, JSON.parse($event.target.value))"
                      class="input-field text-xs">
                      <option v-for="parent in getEligibleParentFields(field)" :key="parent.id"
                        :value="JSON.stringify(parent)" :selected="field.parent_field_id === parent.id">
                        {{ parent.field_label }} ({{ parent.field_name }})
                      </option>
                    </select>
                  </div>

                  <div v-if="field.show_when">
                    <label class="text-xs text-gray-600 mb-1.5 block">Has value:</label>
                    <div class="flex flex-wrap gap-2">
                      <button
                        v-for="opt in (getEligibleParentFields(field).find(p => p.id === field.parent_field_id)?.options || [])"
                        :key="opt" type="button" @click="toggleTriggerOption(field, opt)"
                        class="text-xs px-3 py-1.5 rounded-full border transition-colors" :class="field.show_when[Object.keys(field.show_when)[0]]?.includes(opt)
                          ? 'bg-amber-100 border-amber-300 text-amber-800'
                          : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'">
                        {{ opt }}
                        <span v-if="field.show_when[Object.keys(field.show_when)[0]]?.includes(opt)"
                          class="ml-1">✓</span>
                      </button>
                    </div>
                    <p v-if="!field.show_when[Object.keys(field.show_when)[0]]?.length"
                      class="text-xs text-red-500 mt-1">
                      Select at least one trigger value
                    </p>
                  </div>
                </div>

                <p v-if="!field.show_when && getEligibleParentFields(field).length === 0"
                  class="text-xs text-gray-400 italic">
                  No eligible parent fields. Mark a field above as "Can Have Dependents" with options.
                </p>
              </div>
            </div>

            <div v-if="!customFields.length" class="px-5 py-12 text-center text-gray-400 text-sm">
              No custom fields yet. Click <strong>+ Add Field</strong> to start.
            </div>
          </div>
        </div>

        <p v-if="formData.errors.fields" class="error">{{ formData.errors.fields }}</p>

        <div class="flex justify-end gap-3">
          <Link :href="isEdit ? route('admin.forms.show', form?.id) : route('admin.forms.index')" class="btn-secondary">
            Cancel</Link>
          <button type="submit" :disabled="formData.processing" class="btn-primary">
            {{ formData.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Create Form') }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>