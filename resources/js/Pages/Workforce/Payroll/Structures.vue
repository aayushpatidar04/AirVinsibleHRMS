<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

const props = defineProps({ employees: Object, clients: Array, filters: Object });

const f = reactive({ client_id: props.filters.client_id ?? "", search: props.filters.search ?? "" });
const apply = () => router.get(route("workforce.payroll.structures"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
</script>

<template>
    <Head title="Salary structures" />

    <AppLayout>
        <PageHeader title="Salary structures" subtitle="What each employee earns and what is deducted. A change creates a new version from a date; earlier versions stay for history.">
            <Link :href="route('workforce.payroll.index')" class="text-sm text-indigo-600 hover:underline">← Payroll runs</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <input v-model="f.search" type="search" placeholder="Name or employee ID" class="rounded-lg border-gray-300 text-sm" @keyup.enter="apply" />
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Monthly gross</th><th class="px-4 py-3">Effective from</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="e in employees.data" :key="e.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ e.name }}<p class="text-xs font-normal text-gray-400">{{ e.employee_id }} · {{ e.designation }}</p></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ e.client ?? "—" }}</td>
                        <td class="px-4 py-2.5">
                            <span v-if="e.has_structure" class="font-medium">₹ {{ formatMoney(e.gross) }}</span>
                            <span v-else class="rounded bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-700">No structure</span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ e.effective_from ?? "—" }}</td>
                        <td class="px-4 py-2.5 text-right"><Link :href="route('workforce.payroll.structures.edit', e.id)" class="text-xs font-medium text-indigo-600 hover:underline">{{ e.has_structure ? "View / revise" : "Set up" }}</Link></td>
                    </tr>
                    <tr v-if="!employees.data.length"><td colspan="5" class="px-4 py-10 text-center text-gray-500">No employees match.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="employees.links" />
    </AppLayout>
</template>
