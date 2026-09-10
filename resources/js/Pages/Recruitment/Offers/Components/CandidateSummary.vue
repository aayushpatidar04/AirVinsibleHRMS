<script setup>
import { computed } from "vue";

import {
    BriefcaseBusiness,
    Building2,
    Mail,
    MapPin,
    Phone,
    UserRound,
} from "lucide-vue-next";

const props = defineProps({
    candidate: {
        type: Object,
        required: true,
    },
});

const candidateName = computed(() => {
    if (props.candidate.name) {
        return props.candidate.name;
    }

    return [
        props.candidate.first_name,
        props.candidate.middle_name,
        props.candidate.last_name,
    ]
        .filter(Boolean)
        .join(" ");
});

const initials = computed(() => {
    return candidateName.value
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join("")
        .toUpperCase();
});

const details = computed(() => [
    {
        label: "Email",
        value:
            props.candidate.email ||
            props.candidate.personal_email,
        icon: Mail,
    },
    {
        label: "Phone",
        value:
            props.candidate.phone ||
            props.candidate.mobile,
        icon: Phone,
    },
]);
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div
            class="bg-gradient-to-r from-indigo-50 via-white to-purple-50 px-5 py-5 sm:px-6"
        >
            <div
                class="flex flex-col gap-5 sm:flex-row sm:items-center"
            >
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-indigo-600 text-lg font-bold text-white shadow-sm"
                >
                    <img
                        v-if="
                            candidate.photo_url ||
                            candidate.avatar_url
                        "
                        :src="
                            candidate.photo_url ||
                            candidate.avatar_url
                        "
                        :alt="candidateName"
                        class="h-full w-full object-cover"
                    />

                    <span v-else>
                        {{ initials || "C" }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <div
                        class="flex flex-wrap items-center gap-2"
                    >
                        <h2
                            class="truncate text-xl font-semibold text-gray-900"
                        >
                            {{ candidateName }}
                        </h2>

                        <span
                            v-if="
                                candidate.candidate_code ||
                                candidate.code
                            "
                            class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-gray-200"
                        >
                            {{
                                candidate.candidate_code ||
                                candidate.code
                            }}
                        </span>

                        <span
                            v-if="
                                candidate.final_status
                            "
                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold capitalize text-emerald-700"
                        >
                            {{
                                candidate.final_status
                            }}
                        </span>
                    </div>

                    <p
                        class="mt-1 text-sm text-gray-500"
                    >
                        Review candidate information
                        before preparing the offer.
                    </p>
                </div>
            </div>
        </div>

        <div
            class="grid gap-4 border-t border-gray-200 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-2"
        >
            <div
                v-for="detail in details"
                :key="detail.label"
                class="flex min-w-0 items-start gap-3"
            >
                <div
                    class="rounded-lg bg-gray-100 p-2 text-gray-500"
                >
                    <component
                        :is="detail.icon"
                        class="h-4 w-4"
                    />
                </div>

                <div class="min-w-0">
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        {{ detail.label }}
                    </p>

                    <p
                        class="mt-1 truncate text-sm font-medium text-gray-900"
                    >
                        {{
                            detail.value || "—"
                        }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>