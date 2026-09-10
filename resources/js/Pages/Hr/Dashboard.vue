<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    upcomingInterviews: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="HR Dashboard" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">HR Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="(value, key) in stats"
                        :key="key"
                        class="rounded-xl bg-white p-5 shadow-sm"
                    >
                        <p class="text-sm capitalize text-gray-500">
                            {{ key.replaceAll("_", " ") }}
                        </p>

                        <p class="mt-2 text-3xl font-semibold text-gray-900">
                            {{ value }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Upcoming Interviews
                        </h3>

                        <Link
                            :href="route('recruitment.candidates.index')"
                            class="text-sm font-medium text-indigo-600"
                        >
                            View candidates
                        </Link>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                    >
                                        Candidate
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                    >
                                        Round
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                    >
                                        Interviewer
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                    >
                                        Schedule
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="schedule in upcomingInterviews"
                                    :key="schedule.id"
                                >
                                    <td class="px-3 py-3 text-sm text-gray-900">
                                        {{ schedule.candidate.first_name }}
                                        {{ schedule.candidate.last_name }}
                                    </td>

                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        {{ schedule.round.name }}
                                    </td>

                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        {{ schedule.interviewer.first_name }}
                                        {{ schedule.interviewer.last_name }}
                                    </td>

                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        {{
                                            new Date(
                                                schedule.scheduled_at,
                                            ).toLocaleString()
                                        }}
                                    </td>
                                </tr>

                                <tr v-if="!upcomingInterviews.length">
                                    <td
                                        colspan="4"
                                        class="px-3 py-8 text-center text-sm text-gray-500"
                                    >
                                        No upcoming interviews found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
