<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

const props = defineProps({ payslips: Object, filters: Object, runs: Array, counts: Object });

const f = reactive({ run_id: props.filters.run_id ?? "", status: props.filters.status ?? "", search: props.filters.search ?? "" });
const apply = () => router.get(route("workforce.payroll.payslips"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
</script>

<template>
    <Head title="Payslips" />

    <AppLayout>
        <PageHeader title="Payslips" subtitle="Payslips stay hidden from employees until an administrator publishes the payroll run.">
            <Link :href="route('workforce.payroll.index')" class="text-sm text-indigo-600 hover:underline">← Payroll runs</Link>
        </PageHeader>

        <div class="mb-4 grid grid-cols-2 gap-3 sm:max-w-md">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-emerald-600">{{ counts.published }}</p><p class="text-xs text-gray-500">Visible to employees</p></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-600">{{ counts.hidden }}</p><p class="text-xs text-gray-500">Hidden until published</p></div>
        </div>

        <div class="mb-4 flex flex-wrap gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <input v-model="f.search" type="search" placeholder="Name or employee ID" class="rounded-lg border-gray-300 text-sm" @keyup.enter="apply" />
            <select v-model="f.run_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All runs</option><option v-for="r in runs" :key="r.id" :value="r.id">{{ r.run_number }}</option></select>
            <select v-model="f.status" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All</option><option value="published">Published</option><option value="hidden">Hidden</option></select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Payslip</th><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Period</th><th class="px-4 py-3 text-right">Net pay</th><th class="px-4 py-3">Visibility</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in payslips.data" :key="p.id">
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ p.number }}</td>
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ p.employee }}<p class="text-xs font-normal text-gray-400">{{ p.employee_id }}</p></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.period }}</td>
                        <td class="px-4 py-2.5 text-right font-medium">₹ {{ formatMoney(p.net) }}</td>
                        <td class="px-4 py-2.5"><Pill :status="p.published ? 'published' : 'hidden'" /><p v-if="p.published_at" class="text-xs text-gray-400">{{ p.published_at }}</p></td>
                        <td class="px-4 py-2.5 text-right"><a :href="route('workforce.payroll.payslips.download', p.id)" target="_blank" class="text-xs font-medium text-indigo-600 hover:underline">PDF</a></td>
                    </tr>
                    <tr v-if="!payslips.data.length"><td colspan="6" class="px-4 py-10 text-center text-gray-500">No payslips yet. They are generated when a run is approved.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="payslips.links" />
    </AppLayout>
</template>
