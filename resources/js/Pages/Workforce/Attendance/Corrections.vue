<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ pending: Array, recent: Array });

const approve = (c) => {
    const note = prompt("Approve this correction? Add a note (optional):", "");
    if (note === null) return;
    router.post(route("workforce.attendance.corrections.approve", c.id), { note }, { preserveScroll: true });
};
const reject = (c) => {
    const note = prompt("Why is this being rejected? (required)");
    if (!note) return;
    router.post(route("workforce.attendance.corrections.reject", c.id), { note }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Correction requests" />

    <AppLayout>
        <PageHeader title="Attendance correction requests" subtitle="Approving adds the corrected punches on top of the originals, which are kept for audit." />

        <h2 class="mb-2 text-sm font-semibold text-gray-800">Waiting for a decision ({{ pending.length }})</h2>
        <div v-if="pending.length" class="mb-8 space-y-3">
            <div v-for="c in pending" :key="c.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900">{{ c.employee }} <span class="text-xs font-normal text-gray-400">{{ c.employee_id }}</span></p>
                        <p class="text-sm text-gray-600">{{ c.date }} · wants <b>{{ c.requested_in ?? "(unchanged)" }}</b> → <b>{{ c.requested_out ?? "(unchanged)" }}</b></p>
                        <p class="mt-1 text-sm text-gray-500">"{{ c.reason }}"</p>
                        <p class="mt-1 text-xs text-gray-400">Recorded punches: {{ c.snapshot.length ? c.snapshot.join(", ") : "none" }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('workforce.attendance.employee', { employee: c.user_id })" class="text-xs text-indigo-600 hover:underline">Open month</Link>
                        <template v-if="c.can_decide">
                            <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700" @click="approve(c)">Approve</button>
                            <button class="rounded-lg border border-red-300 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50" @click="reject(c)">Reject</button>
                        </template>
                        <span v-else class="text-xs text-gray-400">Your own request</span>
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="mb-8 text-sm text-gray-500">No pending requests.</p>

        <h2 class="mb-2 text-sm font-semibold text-gray-800">Recently decided</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="c in recent" :key="c.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ c.employee }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ c.date }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ c.requested_in ?? "—" }} → {{ c.requested_out ?? "—" }}</td>
                        <td class="px-4 py-2.5"><Pill :status="c.status" /></td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ c.decided_by }}<span v-if="c.decision_note"> · "{{ c.decision_note }}"</span></td>
                    </tr>
                    <tr v-if="!recent.length"><td class="px-4 py-6 text-center text-gray-500">Nothing yet.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
