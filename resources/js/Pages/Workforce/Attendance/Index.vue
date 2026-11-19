<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { flagLabels, formatMinutes } from "@/Utils/workforce";

const props = defineProps({ filters: Object, clients: Array, statuses: Object, rows: Array, pagination: Object, stats: Object });

const f = reactive({ ...props.filters, client_id: props.filters.client_id ?? "", status: props.filters.status ?? "", search: props.filters.search ?? "" });
const apply = () => router.get(route("workforce.attendance.index"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
const shift = (days) => {
    const d = new Date(f.date);
    d.setDate(d.getDate() + days);
    f.date = d.toISOString().slice(0, 10);
    apply();
};

const cards = [
    ["Employees", "employees", "text-gray-800"], ["Present", "present", "text-emerald-600"], ["Half day", "half_day", "text-amber-600"],
    ["Absent", "absent", "text-red-600"], ["On leave", "leave", "text-sky-600"], ["Off / holiday", "off", "text-slate-600"],
    ["Late", "late", "text-orange-600"], ["Exceptions", "exceptions", "text-orange-600"],
];
const pageLinks = () => {
    const last = Math.ceil(props.pagination.total / props.pagination.per_page);
    if (last <= 1) return [];
    return Array.from({ length: last }, (_, i) => ({ label: String(i + 1), active: i + 1 === props.pagination.current_page, url: route("workforce.attendance.index", { ...props.filters, page: i + 1 }) }));
};
</script>

<template>
    <Head title="Attendance" />

    <AppLayout>
        <PageHeader title="Attendance" subtitle="Who is in, who is late and what needs attention.">
            <Link :href="route('workforce.attendance.exceptions')" class="rounded-lg border border-orange-300 bg-orange-50 px-3 py-2 text-sm font-medium text-orange-800 hover:bg-orange-100">
                Exceptions <span v-if="stats.exceptions" class="ml-1 rounded-full bg-orange-600 px-1.5 text-xs text-white">{{ stats.exceptions }}</span>
            </Link>
            <Link :href="route('workforce.attendance.corrections')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Correction requests</Link>
        </PageHeader>

        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
            <div v-for="c in cards" :key="c[0]" class="rounded-xl bg-white p-3 text-center shadow-sm ring-1 ring-gray-100">
                <p :class="['text-xl font-bold', c[2]]">{{ stats[c[1]] }}</p><p class="text-xs text-gray-500">{{ c[0] }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <button class="rounded-lg border px-2.5 py-1.5 text-sm" @click="shift(-1)">←</button>
            <input v-model="f.date" type="date" class="rounded-lg border-gray-300 text-sm" @change="apply" />
            <button class="rounded-lg border px-2.5 py-1.5 text-sm" @click="shift(1)">→</button>
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply">
                <option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="f.status" class="rounded-lg border-gray-300 text-sm" @change="apply">
                <option value="">All statuses</option><option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option>
            </select>
            <input v-model="f.search" type="search" placeholder="Name or employee ID" class="rounded-lg border-gray-300 text-sm" @keyup.enter="apply" />
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Client / shift</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">In</th><th class="px-4 py-3">Out</th><th class="px-4 py-3">Worked</th><th class="px-4 py-3">Flags</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="r in rows" :key="r.user_id" class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <Link :href="route('workforce.attendance.employee', { employee: r.user_id, month: filters.date.slice(0, 7) })" class="font-medium text-indigo-600 hover:underline">{{ r.name }}</Link>
                            <p class="text-xs text-gray-400">{{ r.employee_id }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.client ?? "—" }}<p class="text-xs text-gray-400">{{ r.shift ?? "No shift" }}</p></td>
                        <td class="px-4 py-2.5"><Pill :status="r.status" /></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.first_in ?? "—" }}<span v-if="r.late_minutes" class="ml-1 text-xs text-red-600">+{{ r.late_minutes }}m</span></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.last_out ?? "—" }}<span v-if="r.early_minutes" class="ml-1 text-xs text-red-600">−{{ r.early_minutes }}m</span></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ formatMinutes(r.worked_minutes) }}</td>
                        <td class="px-4 py-2.5 text-xs">
                            <span v-for="fl in r.flags" :key="fl" class="mr-1 rounded bg-orange-50 px-1.5 py-0.5 text-orange-700">{{ flagLabels[fl] ?? fl }}</span>
                            <span v-if="r.locked" class="text-gray-400">🔒</span>
                        </td>
                    </tr>
                    <tr v-if="!rows.length"><td colspan="7" class="px-4 py-10 text-center text-gray-500">No employees match.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="pageLinks()" />
    </AppLayout>
</template>
