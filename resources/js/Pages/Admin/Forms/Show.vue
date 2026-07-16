<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ form: Object, fields: Array, fieldTypes: Object, qrCodes: Array })

const del = () => {
  if (confirm('Delete this form?'))
    router.delete(route('admin.forms.destroy', props.form.id))
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <Link :href="route('admin.forms.index')" class="text-gray-400 hover:text-gray-600">←</Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ form.name }}</h1>
            <p class="text-sm text-gray-500">{{ form.branch_name }} · v{{ form.version }} · {{ form.total_submissions }} submissions</p>
          </div>
        </div>
        <div class="flex gap-2">
          <span :class="form.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-xs font-medium px-3 py-1 rounded-full">
            {{ form.is_active ? 'Active' : 'Inactive' }}
          </span>
          <Link :href="route('admin.forms.edit', form.id)" class="btn-secondary text-sm">Edit Form</Link>
          <button @click="del" class="btn-danger text-sm">Delete</button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Fields List -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Form Fields ({{ fields.length }})</h2>
            <span class="text-xs text-gray-400">{{ fields.filter(f => f.is_mandatory).length }} required</span>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="(field, i) in fields" :key="field.id" class="px-5 py-4 flex items-center gap-4">
              <span class="text-sm font-bold text-gray-300 w-6 text-center">{{ i + 1 }}</span>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <p class="font-medium text-gray-800">{{ field.field_label }}</p>
                  <span v-if="field.is_mandatory" class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Required</span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ fieldTypes[field.field_type] ?? field.field_type }}
                  <span v-if="field.field_placeholder"> · "{{ field.field_placeholder }}"</span>
                </p>
                <div v-if="field.options && Object.keys(field.options).length" class="flex flex-wrap gap-1 mt-1">
                  <span v-for="(label, key) in field.options" :key="key"
                    class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ label }}</span>
                </div>
              </div>
              <span class="text-xs text-gray-300 font-mono">{{ field.field_name }}</span>
            </div>
            <p v-if="!fields.length" class="px-5 py-10 text-center text-gray-400 text-sm">No fields added yet.</p>
          </div>
        </div>

        <!-- Side Info -->
        <div class="space-y-4">
          <!-- Form Info -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Form Details</h2>
            <dl class="space-y-2 text-sm">
              <div class="flex gap-2"><dt class="w-24 text-gray-500">Branch</dt><dd>{{ form.branch_name }}</dd></div>
              <div class="flex gap-2"><dt class="w-24 text-gray-500">Version</dt><dd>v{{ form.version }}</dd></div>
              <div class="flex gap-2"><dt class="w-24 text-gray-500">Submissions</dt><dd class="font-semibold text-indigo-600">{{ form.total_submissions }}</dd></div>
              <div v-if="form.description" class="pt-2 border-t">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-gray-700">{{ form.description }}</p>
              </div>
            </dl>
          </div>

          <!-- QR Codes using this form -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold text-gray-800">QR Codes</h2>
              <Link :href="route('admin.qrcodes.create')" class="text-xs text-indigo-600 hover:underline">+ New QR</Link>
            </div>
            <div class="space-y-2">
              <div v-for="qr in qrCodes" :key="qr.id" class="flex items-center justify-between text-sm">
                <div>
                  <p class="font-medium text-gray-700">{{ qr.label }}</p>
                  <p class="text-xs text-gray-400">{{ qr.branch?.name }} · Used {{ qr.usage_count }}×</p>
                </div>
                <Link :href="route('admin.qrcodes.show', qr.id)" class="text-indigo-600 text-xs hover:underline">View</Link>
              </div>
              <p v-if="!qrCodes?.length" class="text-xs text-gray-400 text-center py-2">No QR codes yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>