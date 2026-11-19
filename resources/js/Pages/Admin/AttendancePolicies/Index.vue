<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ policies: Object, clients: Array, filters: Object });

const f = reactive({ client_id: props.filters.client_id ?? "", search: props.filters.search ?? "" });
const apply = () => router.get(route("admin.attendance-policies.index"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });
const remove = (p) => confirm(`Remove "${p.name}"? Past attendance keeps the rules that were applied.`) && router.delete(route("admin.attendance-policies.destroy", p.id), { preserveScroll: true });
</script>

<template>
    <Head title="Attendance policies" />

    <AppLayout>
        <PageHeader title="Attendance policies" subtitle="Rules for hours, lateness, breaks, overtime and sources, set per client. Each client's policy applies only to that client's employees.">
            <Link :href="route('admin.attendance-policies.create')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">New policy</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-3">
            <input v-model="f.search" type="search" placeholder="Search policies" class="rounded-lg border-gray-300 text-sm" @keyup.enter="apply" />
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Policy</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Effective</th><th class="px-4 py-3">Rules</th><th class="px-4 py-3">Status</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in policies.data" :key="p.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ p.name }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.client }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.effective_from }} → {{ p.effective_to ?? "open" }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ p.summary }}</td>
                        <td class="px-4 py-2.5"><span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', p.current ? 'bg-emerald-100 text-emerald-800' : p.is_active ? 'bg-sky-100 text-sky-800' : 'bg-gray-100 text-gray-500']">{{ p.current ? "In force" : p.is_active ? "Scheduled / ended" : "Inactive" }}</span></td>
                        <td class="px-4 py-2.5 text-right text-xs">
                            <Link :href="route('admin.attendance-policies.edit', p.id)" class="mr-3 font-medium text-indigo-600 hover:underline">Edit</Link>
                            <button class="font-medium text-red-600 hover:underline" @click="remove(p)">Remove</button>
                        </td>
                    </tr>
                    <tr v-if="!policies.data.length"><td colspan="6" class="px-4 py-10 text-center text-gray-500">No policies yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="policies.links" />
    </AppLayout>
</template>
