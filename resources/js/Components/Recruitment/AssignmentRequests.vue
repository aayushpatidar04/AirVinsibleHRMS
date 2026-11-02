<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
    requests: {
        type: Array,
        default: () => [],
    },
});

const respond = (assignment, response) => {
    router.post(
        route(`interviewer.interviews.assignment.${response}`, {
            candidate: assignment.candidate_id,
            round: assignment.round_id,
        }),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <section
        v-if="requests.length"
        class="surface overflow-hidden border border-violet-100"
    >
        <div class="border-b border-indigo-50 px-6 py-4">
            <h2 class="font-display text-base font-bold">
                Interview Assignment Requests ({{ requests.length }})
            </h2>
            <p class="mt-1 text-sm text-ink-soft">
                Accept an assignment to access the candidate and conduct the interview.
            </p>
        </div>

        <div class="divide-y divide-indigo-50">
            <div
                v-for="assignment in requests"
                :key="assignment.id"
                class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"
            >
                <div>
                    <p class="font-semibold text-ink">
                        {{ assignment.candidate_name }}
                    </p>
                    <p class="mt-0.5 text-sm text-ink-soft">
                        {{ assignment.position }} · {{ assignment.round_name }}
                        <template v-if="assignment.branch_name">
                            · {{ assignment.branch_name }}
                        </template>
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn-secondary text-sm"
                        @click="respond(assignment, 'reject')"
                    >
                        Reject
                    </button>
                    <button
                        type="button"
                        class="btn-primary text-sm"
                        @click="respond(assignment, 'accept')"
                    >
                        Accept
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
