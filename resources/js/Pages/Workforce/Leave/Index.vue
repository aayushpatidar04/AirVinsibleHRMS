<script setup>
import { reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ applications: Object, filters: Object, clients: Array, pendingCount: Number });

const f = reactive({ status: props.filters.status ?? "pending", client_id: props.filters.client_id ?? "" });
const apply = () => router.get(route("workforce.leave.index"), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== "")), { preserveState: true, replace: true });

const approve = (a) => {
    const note = prompt(`Approve ${a.days} day(s) of ${a.type} for ${a.employee}? Add a note (optional):`, "");
    if (note !== null) router.post(route("workforce.leave.approve", a.id), { note }, { preserveScroll: true });
};
const reject = (a) => {
    const note = prompt("Why is this being rejected? (required)");
    if (note) router.post(route("workforce.leave.reject", a.id), { note }, { preserveScroll: true });
};
const cancel = (a) => confirm("Cancel this leave? Any balance used is returned.") && router.post(route("workforce.leave.cancel", a.id), {}, { preserveScroll: true });

const tabs = [["pending", "Pending"], ["approved", "Approved"], ["rejected", "Rejected"], ["cancelled", "Cancelled"], ["all", "All"]];
</script>

<template>
    <Head title="Leave requests" />

    <AppLayout>
        <PageHeader title="Leave requests" subtitle="Requests from your team. Approving updates the balance and marks the days on attendance.">
            <Link :href="route('workforce.leave.balances')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Balances & adjustments</Link>
        </PageHeader>

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <button v-for="t in tabs" :key="t[0]" :class="['rounded-full px-4 py-1.5 text-sm font-medium', f.status === t[0] ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50']" @click="f.status = t[0]; apply()">
                {{ t[1] }}<span v-if="t[0] === 'pending' && pendingCount" class="ml-1 rounded-full bg-orange-500 px-1.5 text-xs text-white">{{ pendingCount }}</span>
            </button>
            <select v-model="f.client_id" class="ml-auto rounded-lg border-gray-300 text-sm" @change="apply">
                <option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <div class="space-y-3">
            <div v-for="a in applications.data" :key="a.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900">{{ a.employee }} <span class="text-xs font-normal text-gray-400">{{ a.employee_id }}</span></p>
                        <p class="mt-0.5 text-sm text-gray-600">
                            <b>{{ a.type }}</b> · {{ a.start_date }}<span v-if="a.start_date !== a.end_date"> – {{ a.end_date }}</span>
                            · <b>{{ a.days }}</b> day(s)<span v-if="a.half_day" class="capitalize"> ({{ a.half_day }})</span>
                        </p>
                        <p class="mt-1 text-sm text-gray-500">"{{ a.reason }}"</p>
                        <p class="mt-1 text-xs text-gray-400">Current balance: {{ a.balance }}</p>
                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500">
                            <span v-for="(ap, i) in a.approvals" :key="i" class="capitalize">{{ ap.role }}: <b>{{ ap.status }}</b><span v-if="ap.by"> ({{ ap.by }})</span><span v-if="ap.note" class="normal-case"> "{{ ap.note }}"</span></span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <Pill :status="a.status" />
                        <div class="flex gap-2">
                            <template v-if="a.can_decide">
                                <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700" @click="approve(a)">Approve</button>
                                <button class="rounded-lg border border-red-300 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50" @click="reject(a)">Reject</button>
                            </template>
                            <button v-else-if="a.can_cancel && a.status === 'approved'" class="text-xs text-red-600 hover:underline" @click="cancel(a)">Cancel leave</button>
                        </div>
                    </div>
                </div>
            </div>
            <p v-if="!applications.data.length" class="rounded-2xl bg-white p-10 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">No requests here.</p>
        </div>
        <Pagination :links="applications.links" />
    </AppLayout>
</template>
