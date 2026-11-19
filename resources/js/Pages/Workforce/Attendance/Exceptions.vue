<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { flagLabels } from "@/Utils/workforce";

const props = defineProps({ records: Object, filters: Object, clients: Array });
const f = reactive({ ...props.filters, client_id: props.filters.client_id ?? "" });
const apply = () => router.get(route("workforce.attendance.exceptions"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
const markReviewed = (r) => {
    const note = prompt("Add a note (optional) and confirm that you have reviewed this day:");
    if (note === null) return;
    router.post(route("workforce.attendance.records.review", r.id), { note }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Attendance exceptions" />

    <AppLayout>
        <PageHeader title="Attendance exceptions" subtitle="Days that need a person to look at them before payroll. Fix the punches, approve a correction, or mark the day as reviewed." />

        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <label class="text-sm text-gray-500">From <input v-model="f.from" type="date" class="ml-1 rounded-lg border-gray-300 text-sm" @change="apply" /></label>
            <label class="text-sm text-gray-500">To <input v-model="f.to" type="date" class="ml-1 rounded-lg border-gray-300 text-sm" @change="apply" /></label>
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply">
                <option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">In / out</th><th class="px-4 py-3">Why</th><th class="px-4 py-3" /></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="r in records.data" :key="r.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ r.date_label }}</td>
                        <td class="px-4 py-2.5">{{ r.employee }}<p class="text-xs text-gray-400">{{ r.employee_id }} · {{ r.client }}</p></td>
                        <td class="px-4 py-2.5"><Pill :status="r.status" /></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.first_in ?? "—" }} → {{ r.last_out ?? "—" }}</td>
                        <td class="px-4 py-2.5 text-xs"><span v-for="fl in r.flags" :key="fl" class="mr-1 rounded bg-orange-50 px-1.5 py-0.5 text-orange-700">{{ flagLabels[fl] ?? fl }}</span></td>
                        <td class="px-4 py-2.5 text-right text-xs">
                            <Link :href="route('workforce.attendance.employee', { employee: r.user_id, month: r.date.slice(0, 7) })" class="mr-3 font-medium text-indigo-600 hover:underline">Open</Link>
                            <button v-if="!r.locked" class="font-medium text-emerald-700 hover:underline" @click="markReviewed(r)">Mark reviewed</button>
                            <span v-else class="text-gray-400">🔒 locked</span>
                        </td>
                    </tr>
                    <tr v-if="!records.data.length"><td colspan="6" class="px-4 py-10 text-center text-gray-500">Nothing to review. 🎉</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="records.links" />
    </AppLayout>
</template>
