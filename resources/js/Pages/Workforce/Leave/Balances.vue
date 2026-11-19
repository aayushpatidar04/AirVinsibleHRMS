<script setup>
import { reactive, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ employees: Object, filters: Object, clients: Array, leaveTypes: Array, selected: Object, ledger: Array, canAdjust: Boolean });

const f = reactive({ search: props.filters.search ?? "", client_id: props.filters.client_id ?? "" });
const apply = () => router.get(route("workforce.leave.balances"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });

const showAdjust = ref(false);
const form = useForm({ user_id: "", leave_type_id: "", days: "", reason: "" });

const adjust = (employee) => {
    form.reset();
    form.clearErrors();
    form.user_id = employee.id;
    form.leave_type_id = props.leaveTypes[0]?.id ?? "";
    showAdjust.value = true;
};

const submit = () => form.post(route("workforce.leave.adjust"), { preserveScroll: true, onSuccess: () => (showAdjust.value = false) });
</script>

<template>
    <Head title="Leave balances" />

    <AppLayout>
        <PageHeader title="Leave balances" subtitle="Balances come from the leave ledger: every credit, use and adjustment is recorded with who did it and why.">
            <Link :href="route('workforce.leave.index')" class="text-sm text-indigo-600 hover:underline">← Leave requests</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
            <input v-model="f.search" type="search" placeholder="Name or employee ID" class="rounded-lg border-gray-300 text-sm" @keyup.enter="apply" />
            <select v-model="f.client_id" class="rounded-lg border-gray-300 text-sm" @change="apply">
                <option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Balances</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="e in employees.data" :key="e.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ e.name }}<p class="text-xs font-normal text-gray-400">{{ e.employee_id }}</p></td>
                        <td class="px-4 py-2.5">
                            <span v-for="b in e.balances" :key="b.leave_type_id" class="mr-2 inline-block rounded-lg bg-gray-50 px-2.5 py-1 text-xs">
                                <b>{{ b.code }}</b> {{ b.balance }}<span v-if="b.pending" class="text-amber-600"> (−{{ b.pending }} pending)</span>
                            </span>
                            <span v-if="!e.balances.length" class="text-xs text-gray-400">No leave policy</span>
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs">
                            <Link :href="route('workforce.leave.balances', { ...filters, employee: e.id })" class="mr-3 font-medium text-indigo-600 hover:underline">Ledger</Link>
                            <button v-if="canAdjust" class="font-medium text-gray-700 hover:underline" @click="adjust(e)">Adjust</button>
                        </td>
                    </tr>
                    <tr v-if="!employees.data.length"><td colspan="3" class="px-4 py-10 text-center text-gray-500">No employees match.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="employees.links" />

        <div v-if="selected" class="mt-8">
            <h2 class="mb-2 text-sm font-semibold text-gray-800">Ledger · {{ selected.name }}</h2>
            <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(l, i) in ledger" :key="i">
                            <td class="px-4 py-2 text-gray-600">{{ l.date }}</td>
                            <td class="px-4 py-2 font-medium text-gray-800">{{ l.type }}</td>
                            <td class="px-4 py-2 capitalize text-gray-500">{{ l.entry }}</td>
                            <td :class="['px-4 py-2 font-semibold', l.days >= 0 ? 'text-emerald-700' : 'text-red-600']">{{ l.days > 0 ? "+" : "" }}{{ l.days }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ l.remarks }}<span v-if="l.by"> · {{ l.by }}</span></td>
                        </tr>
                        <tr v-if="!ledger.length"><td class="px-4 py-6 text-center text-gray-500">No entries yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showAdjust" title="Adjust leave balance" subtitle="Use a positive number to add days and a negative number to remove them." @close="showAdjust = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700">Leave type</label>
                    <select v-model="form.leave_type_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }}</option></select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Days (+/−)</label>
                    <input v-model="form.days" type="number" step="0.5" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Reason</label>
                    <textarea v-model="form.reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="form.errors.reason || form.errors.adjust" class="mt-1 text-xs text-red-600">{{ form.errors.reason ?? form.errors.adjust }}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="showAdjust = false">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save adjustment</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
