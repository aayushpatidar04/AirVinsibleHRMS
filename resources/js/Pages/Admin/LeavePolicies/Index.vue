<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ policies: Object, clients: Array, leaveTypes: Array, filters: Object });

const f = reactive({ client_id: props.filters.client_id ?? "", leave_type_id: props.filters.leave_type_id ?? "" });
const apply = () => router.get(route("admin.leave-policies.index"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
const remove = (p) => confirm("Remove this leave policy? Employees covered by it lose access to this leave type.") && router.delete(route("admin.leave-policies.destroy", p.id), { preserveScroll: true });
</script>

<template>
    <Head title="Leave policies" />

    <AppLayout>
        <PageHeader title="Leave policies" subtitle="Entitlement, accrual, carry-forward and approval rules per leave type. A type with no policy is not offered to employees.">
            <Link :href="route('admin.leave-types.index')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Leave types</Link>
            <Link :href="route('admin.leave-policies.create')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">New policy</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <select v-model="f.leave_type_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All leave types</option><option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Leave type</th><th class="px-4 py-3">Applies to</th><th class="px-4 py-3">Effective</th><th class="px-4 py-3">Accrual</th><th class="px-4 py-3">Approval</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in policies.data" :key="p.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ p.leave_type }}<span :class="['ml-2 rounded px-1.5 text-xs', p.is_paid ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ p.is_paid ? "paid" : "unpaid" }}</span><span v-if="!p.is_active" class="ml-2 text-xs text-gray-400">inactive</span></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.client }}<p class="text-xs capitalize text-gray-400">{{ p.employment_type }}</p></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.effective_from }} → {{ p.effective_to ?? "open" }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.accrual }}</td>
                        <td class="px-4 py-2.5 text-xs capitalize text-gray-500">{{ p.levels }}</td>
                        <td class="px-4 py-2.5 text-right text-xs"><Link :href="route('admin.leave-policies.edit', p.id)" class="mr-3 font-medium text-indigo-600 hover:underline">Edit</Link><button class="font-medium text-red-600 hover:underline" @click="remove(p)">Remove</button></td>
                    </tr>
                    <tr v-if="!policies.data.length"><td colspan="6" class="px-4 py-10 text-center text-gray-500">No leave policies yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="policies.links" />
    </AppLayout>
</template>
