<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/Common/StatusBadge.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    comparison: {
        type: Array,
        default: () => [],
    },
})

const candidates = computed(() => props.comparison ?? [])

const categoryKeys = computed(() => {
    const categories = new Map()

    candidates.value.forEach((item) => {
        item.scorecard?.categories?.forEach((category) => {
            if (!categories.has(category.key)) {
                categories.set(category.key, category.label)
            }
        })
    })

    return Array.from(categories, ([key, label]) => ({
        key,
        label,
    }))
})

const roundKeys = computed(() => {
    const rounds = new Map()

    candidates.value.forEach((item) => {
        item.scorecard?.rounds?.forEach((round) => {
            const key = round.round_id ?? round.round_name

            if (!rounds.has(key)) {
                rounds.set(key, {
                    key,
                    label: round.round_name,
                })
            }
        })
    })

    return Array.from(rounds.values())
})

const getCategory = (item, categoryKey) => {
    return item.scorecard?.categories?.find(
        (category) => category.key === categoryKey
    )
}

const getRound = (item, roundKey) => {
    return item.scorecard?.rounds?.find((round) => {
        return (round.round_id ?? round.round_name) === roundKey
    })
}

const formatScore = (score) => {
    if (
        score === null ||
        score === undefined ||
        score === ''
    ) {
        return '—'
    }

    return Number(score).toFixed(2)
}

const formatCurrency = (amount) => {
    if (
        amount === null ||
        amount === undefined ||
        amount === ''
    ) {
        return '—'
    }

    return `₹${Number(amount).toLocaleString('en-IN')}`
}

const scoreTextClass = (score) => {
    const value = Number(score)

    if (value >= 4.5) {
        return 'text-green-700'
    }

    if (value >= 3.75) {
        return 'text-emerald-700'
    }

    if (value >= 3) {
        return 'text-amber-700'
    }

    if (value >= 2) {
        return 'text-orange-700'
    }

    return 'text-red-700'
}

const scoreBackgroundClass = (score) => {
    const value = Number(score)

    if (value >= 4.5) {
        return 'bg-green-50 border-green-200'
    }

    if (value >= 3.75) {
        return 'bg-emerald-50 border-emerald-200'
    }

    if (value >= 3) {
        return 'bg-amber-50 border-amber-200'
    }

    if (value >= 2) {
        return 'bg-orange-50 border-orange-200'
    }

    return 'bg-red-50 border-red-200'
}

const recommendationClasses = (tone) => {
    return {
        success:
            'bg-green-100 text-green-700 border-green-200',

        warning:
            'bg-amber-100 text-amber-700 border-amber-200',

        danger:
            'bg-red-100 text-red-700 border-red-200',
    }[tone] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}

const profileLabel = (candidate) => {
    return candidate.profile ?? '—'
}

const candidateName = (item) => {
    return item.candidate?.name ?? 'Unknown Candidate'
}

const bestOverallScore = computed(() => {
    const scores = candidates.value
        .map((item) => Number(item.scorecard?.overall_average))
        .filter((score) => !Number.isNaN(score))

    return scores.length ? Math.max(...scores) : null
})

const isBestOverallScore = (score) => {
    if (
        bestOverallScore.value === null ||
        score === null ||
        score === undefined
    ) {
        return false
    }

    return Number(score) === bestOverallScore.value
}

const bestCategoryScore = (categoryKey) => {
    const scores = candidates.value
        .map((item) => {
            return Number(
                getCategory(item, categoryKey)?.average
            )
        })
        .filter((score) => !Number.isNaN(score))

    return scores.length ? Math.max(...scores) : null
}

const isBestCategoryScore = (item, categoryKey) => {
    const score = getCategory(item, categoryKey)?.average
    const best = bestCategoryScore(categoryKey)

    if (
        score === null ||
        score === undefined ||
        best === null
    ) {
        return false
    }

    return Number(score) === best
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-wrap items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('recruitment.candidates.index')"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700"
                    >
                        ←
                    </Link>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Candidate Comparison
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Comparing {{ candidates.length }}
                            candidates across interview performance,
                            profile and hiring details
                        </p>
                    </div>
                </div>

                <Link
                    :href="route('recruitment.candidates.index')"
                    class="btn-secondary text-sm"
                >
                    Change Selection
                </Link>
            </div>

            <!-- Empty state -->
            <div
                v-if="candidates.length < 2"
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl"
                >
                    👥
                </div>

                <h2 class="mt-4 font-semibold text-gray-800">
                    Select at least two candidates
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Return to the candidate list and select between
                    two and five candidates for comparison.
                </p>

                <Link
                    :href="route('recruitment.candidates.index')"
                    class="btn-primary mt-5 inline-flex"
                >
                    Go to Candidates
                </Link>
            </div>

            <template v-else>
                <!-- Desktop comparison table -->
                <div
                    class="hidden overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm lg:block"
                >
                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full border-collapse text-sm"
                        >
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="sticky left-0 z-20 min-w-[220px] border-b border-r border-gray-100 bg-gray-50 px-5 py-4 text-left font-semibold text-gray-600"
                                    >
                                        Comparison Area
                                    </th>

                                    <th
                                        v-for="item in candidates"
                                        :key="item.candidate.id"
                                        class="min-w-[250px] border-b border-gray-100 px-5 py-4 text-left"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <Link
                                                    :href="
                                                        route(
                                                            'recruitment.candidates.show',
                                                            item.candidate.id
                                                        )
                                                    "
                                                    class="font-semibold text-gray-900 hover:text-indigo-600"
                                                >
                                                    {{
                                                        candidateName(
                                                            item
                                                        )
                                                    }}
                                                </Link>

                                                <p
                                                    class="mt-0.5 truncate text-xs font-normal text-gray-400"
                                                >
                                                    {{
                                                        item.candidate
                                                            .email
                                                    }}
                                                </p>
                                            </div>

                                            <Link
                                                :href="
                                                    route(
                                                        'recruitment.candidates.show',
                                                        item.candidate.id
                                                    )
                                                "
                                                class="text-xs font-medium text-indigo-600 hover:underline"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Candidate profile section -->
                                <tr>
                                    <td
                                        :colspan="
                                            candidates.length + 1
                                        "
                                        class="bg-indigo-50 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-indigo-700"
                                    >
                                        Candidate Profile
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Position
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`position-${item.candidate.id}`"
                                        class="px-5 py-4 font-medium text-gray-800"
                                    >
                                        {{
                                            item.candidate.position ??
                                            '—'
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Profile
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`profile-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            profileLabel(
                                                item.candidate
                                            )
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Branch
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`branch-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            item.candidate.branch ??
                                            '—'
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Current Round
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`round-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            item.candidate
                                                .current_round ?? '—'
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Current Status
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`status-${item.candidate.id}`"
                                        class="px-5 py-4"
                                    >
                                        <StatusBadge
                                            :status="
                                                item.candidate
                                                    .current_status
                                            "
                                        />
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Final Status
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`final-${item.candidate.id}`"
                                        class="px-5 py-4"
                                    >
                                        <StatusBadge
                                            :status="
                                                item.candidate
                                                    .final_status
                                            "
                                            type="final"
                                        />
                                    </td>
                                </tr>

                                <!-- Overall evaluation -->
                                <tr>
                                    <td
                                        :colspan="
                                            candidates.length + 1
                                        "
                                        class="bg-indigo-50 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-indigo-700"
                                    >
                                        Overall Evaluation
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Overall Score
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`overall-${item.candidate.id}`"
                                        class="px-5 py-4"
                                    >
                                        <div
                                            :class="[
                                                'inline-flex items-center gap-2 rounded-lg border px-3 py-2',
                                                scoreBackgroundClass(
                                                    item.scorecard
                                                        ?.overall_average
                                                ),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'text-lg font-bold',
                                                    scoreTextClass(
                                                        item.scorecard
                                                            ?.overall_average
                                                    ),
                                                ]"
                                            >
                                                {{
                                                    formatScore(
                                                        item.scorecard
                                                            ?.overall_average
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="text-xs text-gray-400"
                                            >
                                                / 5
                                            </span>

                                            <span
                                                v-if="
                                                    isBestOverallScore(
                                                        item.scorecard
                                                            ?.overall_average
                                                    )
                                                "
                                                class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold uppercase text-green-700 shadow-sm"
                                            >
                                                Best
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Recommendation
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`recommendation-${item.candidate.id}`"
                                        class="px-5 py-4"
                                    >
                                        <span
                                            v-if="
                                                item.scorecard
                                                    ?.recommendation
                                            "
                                            :class="[
                                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                                                recommendationClasses(
                                                    item.scorecard
                                                        .recommendation
                                                        .tone
                                                ),
                                            ]"
                                        >
                                            {{
                                                item.scorecard
                                                    .recommendation
                                                    .label
                                            }}
                                        </span>

                                        <span
                                            v-else
                                            class="text-gray-400"
                                        >
                                            —
                                        </span>
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Rated Questions
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`rated-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            item.scorecard
                                                ?.total_rated_questions ??
                                            0
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Completed Rounds
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`completed-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            item.scorecard
                                                ?.completed_rounds ?? 0
                                        }}
                                    </td>
                                </tr>

                                <!-- Category scores -->
                                <template
                                    v-if="categoryKeys.length"
                                >
                                    <tr>
                                        <td
                                            :colspan="
                                                candidates.length + 1
                                            "
                                            class="bg-indigo-50 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-indigo-700"
                                        >
                                            Category Performance
                                        </td>
                                    </tr>

                                    <tr
                                        v-for="category in categoryKeys"
                                        :key="category.key"
                                        class="border-b border-gray-100"
                                    >
                                        <td
                                            class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                        >
                                            {{ category.label }}
                                        </td>

                                        <td
                                            v-for="item in candidates"
                                            :key="`${category.key}-${item.candidate.id}`"
                                            class="px-5 py-4"
                                        >
                                            <div
                                                v-if="
                                                    getCategory(
                                                        item,
                                                        category.key
                                                    )
                                                "
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    :class="[
                                                        'rounded-lg border px-2.5 py-1.5',
                                                        scoreBackgroundClass(
                                                            getCategory(
                                                                item,
                                                                category.key
                                                            )?.average
                                                        ),
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'font-bold',
                                                            scoreTextClass(
                                                                getCategory(
                                                                    item,
                                                                    category.key
                                                                )
                                                                    ?.average
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            formatScore(
                                                                getCategory(
                                                                    item,
                                                                    category.key
                                                                )
                                                                    ?.average
                                                            )
                                                        }}
                                                    </span>

                                                    <span
                                                        class="ml-1 text-xs text-gray-400"
                                                    >
                                                        /5
                                                    </span>
                                                </div>

                                                <div
                                                    class="text-xs text-gray-400"
                                                >
                                                    {{
                                                        getCategory(
                                                            item,
                                                            category.key
                                                        )
                                                            ?.responses_count ??
                                                        0
                                                    }}
                                                    responses
                                                </div>

                                                <span
                                                    v-if="
                                                        isBestCategoryScore(
                                                            item,
                                                            category.key
                                                        )
                                                    "
                                                    class="rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-bold uppercase text-green-700"
                                                >
                                                    Best
                                                </span>
                                            </div>

                                            <span
                                                v-else
                                                class="text-gray-400"
                                            >
                                                —
                                            </span>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Round performance -->
                                <template v-if="roundKeys.length">
                                    <tr>
                                        <td
                                            :colspan="
                                                candidates.length + 1
                                            "
                                            class="bg-indigo-50 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-indigo-700"
                                        >
                                            Round Performance
                                        </td>
                                    </tr>

                                    <tr
                                        v-for="round in roundKeys"
                                        :key="round.key"
                                        class="border-b border-gray-100"
                                    >
                                        <td
                                            class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                        >
                                            {{ round.label }}
                                        </td>

                                        <td
                                            v-for="item in candidates"
                                            :key="`${round.key}-${item.candidate.id}`"
                                            class="px-5 py-4"
                                        >
                                            <div
                                                v-if="
                                                    getRound(
                                                        item,
                                                        round.key
                                                    )
                                                "
                                                class="space-y-1"
                                            >
                                                <p
                                                    :class="[
                                                        'font-bold',
                                                        scoreTextClass(
                                                            getRound(
                                                                item,
                                                                round.key
                                                            )
                                                                ?.average ??
                                                                getRound(
                                                                    item,
                                                                    round.key
                                                                )
                                                                    ?.overall_rating
                                                        ),
                                                    ]"
                                                >
                                                    {{
                                                        formatScore(
                                                            getRound(
                                                                item,
                                                                round.key
                                                            )
                                                                ?.average ??
                                                                getRound(
                                                                    item,
                                                                    round.key
                                                                )
                                                                    ?.overall_rating
                                                        )
                                                    }}
                                                    / 5
                                                </p>

                                                <p
                                                    class="text-xs text-gray-400"
                                                >
                                                    {{
                                                        getRound(
                                                            item,
                                                            round.key
                                                        )
                                                            ?.rated_questions ??
                                                        0
                                                    }}
                                                    rated questions
                                                </p>
                                            </div>

                                            <span
                                                v-else
                                                class="text-gray-400"
                                            >
                                                —
                                            </span>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Salary and hiring -->
                                <tr>
                                    <td
                                        :colspan="
                                            candidates.length + 1
                                        "
                                        class="bg-indigo-50 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-indigo-700"
                                    >
                                        Salary & Hiring
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Final CTC
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`ctc-${item.candidate.id}`"
                                        class="px-5 py-4 font-semibold text-green-700"
                                    >
                                        {{
                                            formatCurrency(
                                                item.candidate
                                                    .final_ctc
                                            )
                                        }}
                                    </td>
                                </tr>

                                <tr class="border-b border-gray-100">
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Final In-Hand
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`in-hand-${item.candidate.id}`"
                                        class="px-5 py-4 font-semibold text-green-700"
                                    >
                                        {{
                                            formatCurrency(
                                                item.candidate
                                                    .final_in_hand
                                            )
                                        }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-100 bg-white px-5 py-4 font-medium text-gray-500"
                                    >
                                        Final Designation
                                    </td>

                                    <td
                                        v-for="item in candidates"
                                        :key="`designation-${item.candidate.id}`"
                                        class="px-5 py-4 text-gray-700"
                                    >
                                        {{
                                            item.candidate
                                                .designation ?? '—'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile and tablet cards -->
                <div class="grid grid-cols-1 gap-5 lg:hidden">
                    <article
                        v-for="item in candidates"
                        :key="item.candidate.id"
                        class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-gray-100 bg-gray-50 px-5 py-4"
                        >
                            <div
                                class="flex items-start justify-between gap-3"
                            >
                                <div>
                                    <Link
                                        :href="
                                            route(
                                                'recruitment.candidates.show',
                                                item.candidate.id
                                            )
                                        "
                                        class="font-semibold text-gray-900 hover:text-indigo-600"
                                    >
                                        {{ candidateName(item) }}
                                    </Link>

                                    <p
                                        class="mt-0.5 text-xs text-gray-400"
                                    >
                                        {{ item.candidate.email }}
                                    </p>
                                </div>

                                <Link
                                    :href="
                                        route(
                                            'recruitment.candidates.show',
                                            item.candidate.id
                                        )
                                    "
                                    class="text-xs font-medium text-indigo-600"
                                >
                                    View
                                </Link>
                            </div>
                        </div>

                        <div class="space-y-5 p-5">
                            <div
                                class="grid grid-cols-2 gap-3 text-sm"
                            >
                                <div>
                                    <p class="text-xs text-gray-400">
                                        Position
                                    </p>

                                    <p
                                        class="mt-1 font-medium text-gray-800"
                                    >
                                        {{
                                            item.candidate.position ??
                                            '—'
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Profile
                                    </p>

                                    <p
                                        class="mt-1 font-medium text-gray-800"
                                    >
                                        {{
                                            profileLabel(
                                                item.candidate
                                            )
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Current Round
                                    </p>

                                    <p
                                        class="mt-1 font-medium text-gray-800"
                                    >
                                        {{
                                            item.candidate
                                                .current_round ?? '—'
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Status
                                    </p>

                                    <div class="mt-1">
                                        <StatusBadge
                                            :status="
                                                item.candidate
                                                    .current_status
                                            "
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-indigo-100 bg-indigo-50 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wide text-indigo-500"
                                        >
                                            Overall Score
                                        </p>

                                        <p
                                            :class="[
                                                'mt-1 text-3xl font-bold',
                                                scoreTextClass(
                                                    item.scorecard
                                                        ?.overall_average
                                                ),
                                            ]"
                                        >
                                            {{
                                                formatScore(
                                                    item.scorecard
                                                        ?.overall_average
                                                )
                                            }}
                                            <span
                                                class="text-sm font-medium text-gray-400"
                                            >
                                                /5
                                            </span>
                                        </p>
                                    </div>

                                    <span
                                        v-if="
                                            item.scorecard
                                                ?.recommendation
                                        "
                                        :class="[
                                            'rounded-full border px-3 py-1 text-xs font-semibold',
                                            recommendationClasses(
                                                item.scorecard
                                                    .recommendation
                                                    .tone
                                            ),
                                        ]"
                                    >
                                        {{
                                            item.scorecard
                                                .recommendation.label
                                        }}
                                    </span>
                                </div>

                                <p
                                    class="mt-3 text-xs text-indigo-600"
                                >
                                    {{
                                        item.scorecard
                                            ?.total_rated_questions ??
                                        0
                                    }}
                                    rated questions across
                                    {{
                                        item.scorecard
                                            ?.completed_rounds ?? 0
                                    }}
                                    completed rounds
                                </p>
                            </div>

                            <div
                                v-if="
                                    item.scorecard?.categories
                                        ?.length
                                "
                            >
                                <h3
                                    class="text-sm font-semibold text-gray-800"
                                >
                                    Category Scores
                                </h3>

                                <div class="mt-3 space-y-2">
                                    <div
                                        v-for="category in item
                                            .scorecard.categories"
                                        :key="category.key"
                                        class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-700"
                                            >
                                                {{
                                                    category.label
                                                }}
                                            </p>

                                            <p
                                                class="text-xs text-gray-400"
                                            >
                                                {{
                                                    category.responses_count
                                                }}
                                                responses
                                            </p>
                                        </div>

                                        <span
                                            :class="[
                                                'font-bold',
                                                scoreTextClass(
                                                    category.average
                                                ),
                                            ]"
                                        >
                                            {{
                                                formatScore(
                                                    category.average
                                                )
                                            }}
                                            /5
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="
                                    item.scorecard?.rounds?.length
                                "
                            >
                                <h3
                                    class="text-sm font-semibold text-gray-800"
                                >
                                    Round Scores
                                </h3>

                                <div class="mt-3 space-y-2">
                                    <div
                                        v-for="round in item
                                            .scorecard.rounds"
                                        :key="
                                            round.progress_id ??
                                            round.round_id
                                        "
                                        class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-700"
                                            >
                                                {{
                                                    round.round_name
                                                }}
                                            </p>

                                            <p
                                                class="text-xs text-gray-400"
                                            >
                                                {{
                                                    round.rated_questions ??
                                                    0
                                                }}
                                                rated questions
                                            </p>
                                        </div>

                                        <span
                                            :class="[
                                                'font-bold',
                                                scoreTextClass(
                                                    round.average ??
                                                        round.overall_rating
                                                ),
                                            ]"
                                        >
                                            {{
                                                formatScore(
                                                    round.average ??
                                                        round.overall_rating
                                                )
                                            }}
                                            /5
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-sm"
                            >
                                <div>
                                    <p class="text-xs text-gray-400">
                                        Final CTC
                                    </p>

                                    <p
                                        class="mt-1 font-semibold text-green-700"
                                    >
                                        {{
                                            formatCurrency(
                                                item.candidate
                                                    .final_ctc
                                            )
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Final In-Hand
                                    </p>

                                    <p
                                        class="mt-1 font-semibold text-green-700"
                                    >
                                        {{
                                            formatCurrency(
                                                item.candidate
                                                    .final_in_hand
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Disclaimer -->
                <div
                    class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4"
                >
                    <span class="text-lg">ℹ️</span>

                    <div>
                        <p class="text-sm font-medium text-blue-800">
                            Comparison guidance
                        </p>

                        <p class="mt-1 text-xs leading-5 text-blue-700">
                            Scores and recommendations are calculated
                            from interview ratings. They should
                            support, not replace, the final hiring
                            decision.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>