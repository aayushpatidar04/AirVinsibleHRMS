<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StatCard from '@/Components/Common/StatCard.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ stats: Object })
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <span class="text-sm text-gray-500">{{ new
          Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
          }}</span>
      </div>

      <!-- Top Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <StatCard title="Total Candidates" :value="stats.total_candidates" icon="👥" color="indigo" />
        <StatCard title="Branches" :value="stats.total_branches" icon="🏢" color="blue" />
        <StatCard title="Interviewers" :value="stats.total_interviewers" icon="🎙️" color="teal" />
        <StatCard title="Pending Interviews" :value="stats.pending_interviews" icon="⏳" color="yellow" />
        <StatCard title="Selected" :value="stats.selected_candidates" icon="✅" color="green"
          :subtitle="`${stats.selection_rate}% selection rate`" />
      </div>

      <!-- Candidate Status Distribution -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h2 class="text-base font-semibold text-gray-800 mb-4">Candidate Status</h2>
          <div class="space-y-3">
            <div v-for="(count, status) in stats.candidates_by_status" :key="status" class="flex items-center gap-3">
              <StatusBadge :status="status" class="w-36" />
              <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div class="bg-indigo-500 h-2 rounded-full transition-all"
                  :style="{ width: stats.total_candidates > 0 ? (count / stats.total_candidates * 100) + '%' : '0%' }" />
              </div>
              <span class="text-sm font-semibold text-gray-700 w-8 text-right">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Profile Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h2 class="text-base font-semibold text-gray-800 mb-4">Profile Category</h2>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-100">
              <div>
                <p class="text-sm font-semibold text-orange-800">Advisor / Executive</p>
                <p class="text-xs text-orange-600">Salary range captured in OPS, finalised in HR</p>
              </div>
              <span class="text-2xl font-bold text-orange-700">{{ stats.candidates_by_profile.advisory_executive
              }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-100">
              <div>
                <p class="text-sm font-semibold text-blue-800">TL / QA / AM / OM</p>
                <p class="text-xs text-blue-600">Salary mandatory in HR round</p>
              </div>
              <span class="text-2xl font-bold text-blue-700">{{ stats.candidates_by_profile.leadership }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
              <div>
                <p class="text-sm font-semibold text-gray-800">Other Profiles</p>
                <p class="text-xs text-gray-500">Standard process</p>
              </div>
              <span class="text-2xl font-bold text-gray-700">{{ stats.candidates_by_profile.other }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Round Stats -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Interview Rounds Overview</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-3 font-medium text-gray-500">Round</th>
                <th class="pb-3 font-medium text-gray-500 text-center">Total</th>
                <th class="pb-3 font-medium text-gray-500 text-center">Pending</th>
                <th class="pb-3 font-medium text-gray-500 text-center">Completed</th>
                <th class="pb-3 font-medium text-gray-500 text-center">Rejected</th>
                <th class="pb-3 font-medium text-gray-500 text-center">Avg Rating</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in stats.round_stats" :key="r.id" class="border-b border-gray-50 hover:bg-gray-50">
                <td class="py-3 font-medium text-gray-800">{{ r.name }}</td>
                <td class="py-3 text-center text-gray-600">{{ r.stats.total }}</td>
                <td class="py-3 text-center"><span class="text-yellow-700 font-medium">{{ r.stats.pending }}</span></td>
                <td class="py-3 text-center"><span class="text-green-700 font-medium">{{ r.stats.completed }}</span>
                </td>
                <td class="py-3 text-center"><span class="text-red-700 font-medium">{{ r.stats.rejected }}</span></td>
                <td class="py-3 text-center">
                  <span class="text-indigo-600 font-semibold">{{ r.stats.avg_rating > 0 ? r.stats.avg_rating + ' ★' :
                    '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Candidates -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-gray-800">Recent Candidates</h2>
          <Link :href="route('admin.candidates.index')" class="text-sm text-indigo-600 hover:underline">View all →
          </Link>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-2 font-medium text-gray-500">Name</th>
                <th class="pb-2 font-medium text-gray-500">Position</th>
                <th class="pb-2 font-medium text-gray-500">Profile</th>
                <th class="pb-2 font-medium text-gray-500">Branch</th>
                <th class="pb-2 font-medium text-gray-500">Status</th>
                <th class="pb-2 font-medium text-gray-500">Round</th>
                <th class="pb-2 font-medium text-gray-500">Registered</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in stats.recent_candidates" :key="c.id" class="border-b border-gray-50 hover:bg-gray-50">
                <td class="py-3">
                  <Link :href="route('admin.candidates.show', c.id)"
                    class="font-medium text-indigo-600 hover:underline">
                    {{ c.name }}
                  </Link>
                </td>
                <td class="py-3 text-gray-600">{{ c.position }}</td>
                <td class="py-3 text-gray-500 text-xs">{{ c.profile }}</td>
                <td class="py-3 text-gray-600">{{ c.branch }}</td>
                <td class="py-3">
                  <StatusBadge :status="c.status" />
                </td>
                <td class="py-3 text-gray-500">{{ c.round ?? '—' }}</td>
                <td class="py-3 text-gray-500">{{ c.registered }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>