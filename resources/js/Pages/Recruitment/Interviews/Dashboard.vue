<script setup>
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Common/StatCard.vue";

const props = defineProps({
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
    canSeeAllBranches: { type: Boolean, default: false },
    stats: { type: Object, required: true },
    scheduled: { type: Array, default: () => [] },
    ongoing: { type: Array, default: () => [] },
    pending: { type: Array, default: () => [] },
    branchReport: { type: Object, required: true },
});

const form = reactive({
    branch_id: props.filters.branch_id ?? "",
    round_id: props.filters.round_id ?? "",
    from: props.filters.from ?? "",
    to: props.filters.to ?? "",
});

const hasFilters = computed(() => Object.values(form).some((v) => v !== ""));

const applyFilters = () => {
    const query = Object.fromEntries(
        Object.entries(form).filter(([, v]) => v !== "" && v !== null),
    );
    router.get(route("recruitment.interviews.dashboard"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    Object.keys(form).forEach((k) => (form[k] = ""));
    applyFilters();
};

const activeTab = ref("scheduled");

const tabs = computed(() => [
    { key: "scheduled", label: "Scheduled", count: props.stats.scheduled },
    { key: "ongoing", label: "Ongoing", count: props.stats.ongoing },
    { key: "pending", label: "Pending", count: props.stats.pending },
]);

const rows = computed(() => props[activeTab.value] ?? []);

const emptyText = {
    scheduled: "No scheduled interviews found.",
    ongoing: "No interviews are in progress right now.",
    pending: "No pending interviews found.",
};

const formatRunning = (minutes) => {
    if (minutes === null || minutes === undefined) return "—";
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    return `${h}h ${minutes % 60}m`;
};

const formatDuration = (minutes) =>
    minutes === null || minutes === undefined ? "—" : formatRunning(minutes);

const totals = computed(() => props.branchReport.totals);

const maxActivity = computed(() =>
    Math.max(
        1,
        ...props.branchReport.rows.map(
            (r) => r.scheduled + r.ongoing + r.pending + r.completed,
        ),
    ),
);

const barWidth = (value, row) => {
    const total = row.scheduled + row.ongoing + row.pending + row.completed;
    if (!total) return "0%";
    // bar length is relative to the busiest branch, segment width to the row
    return `${(value / maxActivity.value) * 100}%`;
};

const tdBase = "px-3 py-3 text-sm";
const thBase =
    "px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500";
</script>

<template>
    <Head title="Interview Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Interview Dashboard
                    </h1>
                    <p class="text-sm text-gray-500">
                        Scheduled, ongoing and pending interviews
                        <span v-if="canSeeAllBranches">across all branches</span>
                    </p>
                </div>
                <Link
                    :href="route('recruitment.candidates.index')"
                    class="text-sm font-medium text-indigo-600 hover:underline"
                >
                    View candidates →
                </Link>
            </div>

            <!-- Filters -->
            <div
                class="grid gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5"
            >
                <select
                    v-if="canSeeAllBranches"
                    v-model="form.branch_id"
                    class="rounded-lg border-gray-300 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">
                        {{ b.name }}
                    </option>
                </select>

                <select
                    v-model="form.round_id"
                    class="rounded-lg border-gray-300 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All rounds</option>
                    <option v-for="r in rounds" :key="r.id" :value="r.id">
                        {{ r.name }}
                    </option>
                </select>

                <label class="flex items-center gap-2 text-sm text-gray-500">
                    From
                    <input
                        v-model="form.from"
                        type="date"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        @change="applyFilters"
                    />
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-500">
                    To
                    <input
                        v-model="form.to"
                        type="date"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        @change="applyFilters"
                    />
                </label>

                <button
                    v-if="hasFilters"
                    type="button"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                    @click="resetFilters"
                >
                    Clear filters
                </button>
            </div>
            <p v-if="form.from || form.to" class="-mt-4 text-xs text-gray-400">
                Date range applies to scheduled interviews only. Ongoing and
                pending lists are always current.
            </p>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
                <StatCard title="Scheduled" :value="stats.scheduled" icon="📅" color="indigo" />
                <StatCard title="Today" :value="stats.scheduled_today" icon="🗓️" color="blue" />
                <StatCard title="Overdue" :value="stats.overdue" icon="⚠️" color="red"
                    subtitle="Scheduled time passed" />
                <StatCard title="Ongoing" :value="stats.ongoing" icon="🎙️" color="teal" />
                <StatCard title="Pending" :value="stats.pending" icon="⏳" color="yellow" />
                <StatCard title="Done Today" :value="stats.completed_today" icon="✅" color="green" />
            </div>

            <!-- Interview lists -->
            <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-wrap gap-1 border-b border-gray-100 px-4 pt-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        :class="[
                            '-mb-px border-b-2 px-4 py-2 text-sm font-medium',
                            activeTab === tab.key
                                ? 'border-indigo-600 text-indigo-700'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <div class="overflow-x-auto p-2">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th :class="thBase">Candidate</th>
                                <th :class="thBase">Branch</th>
                                <th :class="thBase">Round</th>
                                <th :class="thBase">Interviewer</th>
                                <template v-if="activeTab === 'scheduled'">
                                    <th :class="thBase">Scheduled for</th>
                                    <th :class="thBase">Status</th>
                                </template>
                                <template v-else-if="activeTab === 'ongoing'">
                                    <th :class="thBase">Started</th>
                                    <th :class="thBase">Running</th>
                                </template>
                                <template v-else>
                                    <th :class="thBase">Waiting since</th>
                                </template>
                                <th :class="thBase" />
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in rows" :key="`${activeTab}-${row.id}`" class="hover:bg-gray-50">
                                <td :class="tdBase">
                                    <Link
                                        :href="route('recruitment.candidates.show', row.candidate_id)"
                                        class="font-medium text-indigo-600 hover:underline"
                                    >
                                        {{ row.candidate }}
                                    </Link>
                                    <p class="text-xs text-gray-400">{{ row.position }}</p>
                                </td>
                                <td :class="[tdBase, 'text-gray-600']">{{ row.branch ?? "—" }}</td>
                                <td :class="[tdBase, 'text-gray-600']">{{ row.round ?? "—" }}</td>
                                <td :class="[tdBase, 'text-gray-600']">{{ row.interviewer ?? "—" }}</td>

                                <template v-if="activeTab === 'scheduled'">
                                    <td :class="[tdBase, 'text-gray-700']">{{ row.scheduled_label }}</td>
                                    <td :class="tdBase">
                                        <span
                                            v-if="row.is_overdue"
                                            class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700"
                                        >
                                            Overdue
                                        </span>
                                        <span
                                            v-else-if="row.is_today"
                                            class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700"
                                        >
                                            Today
                                        </span>
                                        <span
                                            v-else
                                            class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"
                                        >
                                            Upcoming
                                        </span>
                                        <span v-if="!row.email_sent" class="ml-1 text-xs text-amber-600">
                                            · email not sent
                                        </span>
                                    </td>
                                </template>

                                <template v-else-if="activeTab === 'ongoing'">
                                    <td :class="[tdBase, 'text-gray-700']">{{ row.started_at ?? "—" }}</td>
                                    <td :class="[tdBase, 'font-medium text-teal-700']">
                                        {{ formatRunning(row.running_minutes) }}
                                    </td>
                                </template>

                                <template v-else>
                                    <td :class="[tdBase, 'text-gray-700']">{{ row.waiting_since ?? "—" }}</td>
                                </template>

                                <td :class="[tdBase, 'text-right']">
                                    <a
                                        v-if="activeTab === 'scheduled' && row.gmeet_link"
                                        :href="row.gmeet_link"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-xs font-medium text-indigo-600 hover:underline"
                                    >
                                        Meet link
                                    </a>
                                </td>
                            </tr>

                            <tr v-if="!rows.length">
                                <td colspan="7" class="px-3 py-10 text-center text-sm text-gray-500">
                                    {{ emptyText[activeTab] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="rows.length >= 200" class="px-3 pb-2 text-xs text-gray-400">
                        Showing the first 200 records. Use the filters to narrow down.
                    </p>
                </div>
            </div>

            <!-- Branch-wise report -->
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">
                            Branch-wise Interview Report
                        </h2>
                        <p class="text-xs text-gray-400">
                            Interviews grouped by the candidate's branch.
                            Pass rate = completed ÷ (completed + rejected).
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><i class="h-2 w-2 rounded-full bg-indigo-500" />Scheduled</span>
                        <span class="flex items-center gap-1"><i class="h-2 w-2 rounded-full bg-teal-500" />Ongoing</span>
                        <span class="flex items-center gap-1"><i class="h-2 w-2 rounded-full bg-yellow-400" />Pending</span>
                        <span class="flex items-center gap-1"><i class="h-2 w-2 rounded-full bg-green-500" />Completed</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th :class="thBase">Branch</th>
                                <th :class="[thBase, 'min-w-[160px]']">Activity</th>
                                <th :class="[thBase, 'text-center']">Scheduled</th>
                                <th :class="[thBase, 'text-center']">Overdue</th>
                                <th :class="[thBase, 'text-center']">Ongoing</th>
                                <th :class="[thBase, 'text-center']">Pending</th>
                                <th :class="[thBase, 'text-center']">Completed</th>
                                <th :class="[thBase, 'text-center']">Rejected</th>
                                <th :class="[thBase, 'text-center']">Cancelled / No-show</th>
                                <th :class="[thBase, 'text-center']">Pass rate</th>
                                <th :class="[thBase, 'text-center']">Avg rating</th>
                                <th :class="[thBase, 'text-center']">Avg duration</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in branchReport.rows" :key="row.branch_id" class="hover:bg-gray-50">
                                <td :class="[tdBase, 'font-medium text-gray-800']">
                                    {{ row.branch }}
                                    <span v-if="row.code" class="ml-1 text-xs text-gray-400">{{ row.code }}</span>
                                </td>
                                <td :class="tdBase">
                                    <div class="flex h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div class="bg-indigo-500" :style="{ width: barWidth(row.scheduled, row) }" />
                                        <div class="bg-teal-500" :style="{ width: barWidth(row.ongoing, row) }" />
                                        <div class="bg-yellow-400" :style="{ width: barWidth(row.pending, row) }" />
                                        <div class="bg-green-500" :style="{ width: barWidth(row.completed, row) }" />
                                    </div>
                                </td>
                                <td :class="[tdBase, 'text-center font-medium text-indigo-700']">{{ row.scheduled }}</td>
                                <td :class="[tdBase, 'text-center', row.overdue ? 'font-semibold text-red-600' : 'text-gray-400']">
                                    {{ row.overdue }}
                                </td>
                                <td :class="[tdBase, 'text-center font-medium text-teal-700']">{{ row.ongoing }}</td>
                                <td :class="[tdBase, 'text-center font-medium text-yellow-700']">{{ row.pending }}</td>
                                <td :class="[tdBase, 'text-center font-medium text-green-700']">{{ row.completed }}</td>
                                <td :class="[tdBase, 'text-center font-medium text-red-700']">{{ row.rejected }}</td>
                                <td :class="[tdBase, 'text-center text-gray-500']">{{ row.cancelled }} / {{ row.no_show }}</td>
                                <td :class="[tdBase, 'text-center text-gray-700']">
                                    {{ row.pass_rate === null ? "—" : `${row.pass_rate}%` }}
                                </td>
                                <td :class="[tdBase, 'text-center font-semibold text-indigo-600']">
                                    {{ row.avg_rating ? `${row.avg_rating} ★` : "—" }}
                                </td>
                                <td :class="[tdBase, 'text-center text-gray-600']">{{ formatDuration(row.avg_duration) }}</td>
                            </tr>

                            <tr v-if="!branchReport.rows.length">
                                <td colspan="12" class="px-3 py-10 text-center text-sm text-gray-500">
                                    No interview activity to report yet.
                                </td>
                            </tr>
                        </tbody>

                        <tfoot v-if="branchReport.rows.length > 1">
                            <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                                <td :class="tdBase">Total</td>
                                <td :class="tdBase" />
                                <td :class="[tdBase, 'text-center']">{{ totals.scheduled }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.overdue }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.ongoing }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.pending }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.completed }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.rejected }}</td>
                                <td :class="[tdBase, 'text-center']">{{ totals.cancelled }} / {{ totals.no_show }}</td>
                                <td :class="tdBase" colspan="3" />
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
