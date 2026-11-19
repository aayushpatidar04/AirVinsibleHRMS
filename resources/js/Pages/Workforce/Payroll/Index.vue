<script setup>
import { reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

const props = defineProps({ runs: Object, filters: Object, clients: Array, statuses: Object, availablePeriods: Array, canCreate: Boolean });

const f = reactive({ client_id: props.filters.client_id ?? "", status: props.filters.status ?? "" });
const apply = () => router.get(route("workforce.payroll.index"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });

const form = useForm({ payroll_period_id: "" });
const create = () => form.post(route("workforce.payroll.runs.store"));
</script>

<template>
    <Head title="Payroll" />

    <AppLayout>
        <PageHeader title="Payroll runs" subtitle="One run per client and period: calculate, review, approve, then publish payslips.">
            <Link :href="route('workforce.payroll.periods')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Periods</Link>
            <Link :href="route('workforce.payroll.structures')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Salary structures</Link>
            <Link :href="route('workforce.payroll.settings')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Settings</Link>
            <Link :href="route('workforce.payroll.payslips')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Payslips</Link>
        </PageHeader>

        <div v-if="canCreate" class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="create">
                <div class="min-w-[18rem] flex-1">
                    <label class="text-sm font-medium text-gray-700">Start a new run</label>
                    <select v-model="form.payroll_period_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        <option value="" disabled>Choose a payroll period…</option>
                        <option v-for="p in availablePeriods" :key="p.id" :value="p.id">{{ p.label }}</option>
                    </select>
                    <p v-if="!availablePeriods.length" class="mt-1 text-xs text-gray-500">No open period without a run. <Link :href="route('workforce.payroll.periods')" class="text-indigo-600 hover:underline">Create a period</Link> first.</p>
                    <p v-if="form.errors.run" class="mt-1 text-xs text-red-600">{{ form.errors.run }}</p>
                </div>
                <button type="submit" :disabled="form.processing || !form.payroll_period_id" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-50">Create run</button>
            </form>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <select v-model="f.status" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All statuses</option><option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option></select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Run</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Period</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Employees</th><th class="px-4 py-3 text-right">To fix</th><th class="px-4 py-3 text-right">Net pay</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="r in runs.data" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-4 py-2.5"><Link :href="route('workforce.payroll.runs.show', r.id)" class="font-medium text-indigo-600 hover:underline">{{ r.run_number }}</Link><p class="text-xs text-gray-400">v{{ r.version }}</p></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.client }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ r.period }}</td>
                        <td class="px-4 py-2.5"><Pill :status="r.status" :label="r.status_label" /></td>
                        <td class="px-4 py-2.5 text-right">{{ r.employees }}</td>
                        <td class="px-4 py-2.5 text-right" :class="r.exceptions ? 'font-semibold text-orange-600' : 'text-gray-400'">{{ r.exceptions }}</td>
                        <td class="px-4 py-2.5 text-right font-medium">₹ {{ formatMoney(r.net) }}</td>
                    </tr>
                    <tr v-if="!runs.data.length"><td colspan="7" class="px-4 py-10 text-center text-gray-500">No payroll runs yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="runs.links" />
    </AppLayout>
</template>
