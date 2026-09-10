<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge       from '@/Components/Common/StatusBadge.vue'
import Pagination        from '@/Components/Common/Pagination.vue'
import { Link, router }  from '@inertiajs/vue3'
import { ref, watch }    from 'vue'

const props = defineProps({ candidates: Object, filters: Object, statusOptions: Object })
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

watch([search, status], () => {
  router.get(route('interviewer.candidates.index'), { search: search.value, status: status.value },
    { preserveState: true, replace: true })
})
</script>

<template>
  <AppLayout>
    <div class="space-y-5">
      <h1 class="text-2xl font-bold text-gray-900">My Candidates</h1>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
        <input v-model="search" type="text" placeholder="Search name or email..." class="input-field w-64" />
        <select v-model="status" class="input-field w-44">
          <option value="">All Status</option>
          <option v-for="(l, k) in statusOptions" :key="k" :value="k">{{ l }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Candidate</th>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Position</th>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Branch</th>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Status</th>
              <th class="px-5 py-3 text-left font-medium text-gray-500">Current Round</th>
              <th class="px-5 py-3 text-right font-medium text-gray-500">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="c in candidates.data" :key="c.id" class="hover:bg-gray-50">
              <td class="px-5 py-4">
                <p class="font-medium text-gray-900">{{ c.first_name }} {{ c.last_name }}</p>
                <p class="text-xs text-gray-400">{{ c.email }}</p>
              </td>
              <td class="px-5 py-4 text-gray-600">{{ c.position_applied }}</td>
              <td class="px-5 py-4 text-gray-600">{{ c.branch?.name }}</td>
              <td class="px-5 py-4"><StatusBadge :status="c.current_status" /></td>
              <td class="px-5 py-4 text-gray-500 text-xs">{{ c.current_round?.name ?? '—' }}</td>
              <td class="px-5 py-4 text-right">
                <Link :href="route('interviewer.candidates.show', c.id)" class="text-teal-600 hover:underline text-xs">View</Link>
              </td>
            </tr>
            <tr v-if="!candidates.data?.length">
              <td colspan="6" class="px-5 py-10 text-center text-gray-400">No candidates assigned to you.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="candidates.links" />
    </div>
  </AppLayout>
</template>