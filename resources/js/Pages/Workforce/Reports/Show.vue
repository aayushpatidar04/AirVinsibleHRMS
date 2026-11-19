<script setup>
import { computed, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ report: Object, columns: Array, rows: Array, total: Number, filters: Object, clients: Array, departments: Array, runs: Array });

const f = reactive({ ...props.filters });
const query = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "" && v !== null));
const apply = () => router.get(route("workforce.reports.show", props.report.key), query(), { preserveState: true, replace: true });
const exportUrl = computed(() => route("workforce.reports.show", { report: props.report.key, ...query(), export: "csv" }));

const isPayroll = computed(() => props.report.group === "payroll");
const showDates = computed(() => !["leave_balances", "client_employees"].includes(props.report.key));
const alignRight = (v) => typeof v === "number";
const cell = (v) => (v === null || v === undefined || v === "" ? "—" : typeof v === "number" ? v.toLocaleString("en-IN", { maximumFractionDigits: 2 }) : v);
</script>

<template>
    <Head :title="report.title" />

    <AppLayout>
        <PageHeader :title="report.title" :subtitle="report.description">
            <a :href="exportUrl" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Download CSV</a>
            <Link :href="route('workforce.reports.index')" class="text-sm text-indigo-600 hover:underline">← All reports</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <template v-if="showDates">
                <label class="text-xs text-gray-500">From<input v-model="f.from" type="date" class="mt-1 block rounded-lg border-gray-300 text-sm" @change="apply" /></label>
                <label class="text-xs text-gray-500">To<input v-model="f.to" type="date" class="mt-1 block rounded-lg border-gray-300 text-sm" @change="apply" /></label>
            </template>
            <label class="text-xs text-gray-500">Client
                <select v-model="f.client_id" class="mt-1 block rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </label>
            <label v-if="!isPayroll || report.key === 'payroll_statement'" class="text-xs text-gray-500">Department
                <select v-model="f.department" class="mt-1 block rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All</option><option v-for="d in departments" :key="d" :value="d">{{ d }}</option></select>
            </label>
            <label v-if="isPayroll" class="text-xs text-gray-500">Payroll run
                <select v-model="f.run_id" class="mt-1 block rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All</option><option v-for="r in runs" :key="r.id" :value="r.id">{{ r.run_number }}</option></select>
            </label>
            <label class="text-xs text-gray-500">Employee<input v-model="f.search" type="search" placeholder="Name or ID" class="mt-1 block rounded-lg border-gray-300 text-sm" @keyup.enter="apply" /></label>
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="apply">Apply</button>
        </div>

        <p class="mb-2 text-xs text-gray-500">{{ total }} row(s)<span v-if="total > rows.length"> · showing the first {{ rows.length }}; download the CSV for everything</span></p>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th v-for="c in columns" :key="c" class="whitespace-nowrap px-4 py-3">{{ c }}</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="(row, i) in rows" :key="i" class="hover:bg-gray-50">
                        <td v-for="(v, j) in row" :key="j" :class="['whitespace-nowrap px-4 py-2', alignRight(v) ? 'text-right tabular-nums' : 'text-gray-700']">{{ cell(v) }}</td>
                    </tr>
                    <tr v-if="!rows.length"><td :colspan="columns.length" class="px-4 py-10 text-center text-gray-500">No data for these filters.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
