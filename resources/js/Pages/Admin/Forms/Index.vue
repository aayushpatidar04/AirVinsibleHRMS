<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination  from '@/Components/Common/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ forms: Object, branches: Array, filters: Object })

const search   = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const status   = ref(props.filters?.status ?? '')

watch([search, branchId, status], () => {
  router.get(route('admin.forms.index'),
    { search: search.value, branch_id: branchId.value, status: status.value },
    { preserveState: true, replace: true })
})

const del = (form) => {
  if (confirm('Delete this form? This cannot be undone.'))
    router.delete(route('admin.forms.destroy', form.id))
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Registration Forms</h1>
          <p class="text-sm text-gray-500 mt-1">Forms shown to candidates when they scan a QR code</p>
        </div>
        <Link :href="route('admin.forms.create')" class="btn-primary">+ New Form</Link>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
        <input v-model="search" type="text" placeholder="Search form name..." class="input-field w-56" />
        <select v-model="branchId" class="input-field w-44">
          <option value="">All Branches / Global</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <select v-model="status" class="input-field w-36">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Form Name</th>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Branch</th>
              <th class="px-5 py-3 text-center font-medium text-gray-500">Fields</th>
              <th class="px-5 py-3 text-center font-medium text-gray-500">Submissions</th>
              <th class="px-5 py-3 text-center font-medium text-gray-500">Version</th>
              <th class="px-5 py-3 text-center font-medium text-gray-500">Status</th>
              <th class="px-5 py-3 text-right font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="f in forms.data" :key="f.id" class="hover:bg-gray-50">
              <td class="px-5 py-4">
                <p class="font-medium text-gray-900">{{ f.name }}</p>
                <p v-if="f.description" class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ f.description }}</p>
              </td>
              <td class="px-5 py-4 text-gray-600 text-sm">{{ f.branch_name ?? 'Global' }}</td>
              <td class="px-5 py-4 text-center font-semibold text-indigo-600">{{ f.fields_count }}</td>
              <td class="px-5 py-4 text-center text-gray-600">{{ f.submissions_count }}</td>
              <td class="px-5 py-4 text-center text-gray-500">v{{ f.version }}</td>
              <td class="px-5 py-4 text-center">
                <span :class="f.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                  class="text-xs font-medium px-2 py-0.5 rounded-full">
                  {{ f.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-5 py-4 text-right space-x-3">
                <Link :href="route('admin.forms.show', f.id)" class="text-indigo-600 hover:underline text-xs">View</Link>
                <Link :href="route('admin.forms.edit', f.id)" class="text-gray-500 hover:underline text-xs">Edit</Link>
                <button @click="del(f)" class="text-red-400 hover:text-red-600 text-xs">Delete</button>
              </td>
            </tr>
            <tr v-if="!forms.data?.length">
              <td colspan="7" class="px-5 py-12 text-center text-gray-400">No forms found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="forms.links" />
    </div>
  </AdminLayout>
</template>