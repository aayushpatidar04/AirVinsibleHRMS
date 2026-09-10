<script setup>
import { computed } from 'vue'

const props = defineProps({
    scorecard: {
        type: Object,
        default: () => ({
            overall_average: null,
            recommendation: null,
            categories: [],
            rounds: [],
            total_rated_questions: 0,
            completed_rounds: 0,
        }),
    },
})

const hasScorecardData = computed(() => {
    return (
        props.scorecard?.overall_average !== null ||
        props.scorecard?.categories?.length > 0 ||
        props.scorecard?.rounds?.length > 0
    )
})

const recommendationClasses = computed(() => {
    const tone = props.scorecard?.recommendation?.tone

    return {
        success: {
            wrapper: 'border-green-200 bg-green-50',
            badge: 'bg-green-100 text-green-700 ring-green-200',
            icon: 'bg-green-100 text-green-700',
        },

        warning: {
            wrapper: 'border-amber-200 bg-amber-50',
            badge: 'bg-amber-100 text-amber-700 ring-amber-200',
            icon: 'bg-amber-100 text-amber-700',
        },

        danger: {
            wrapper: 'border-red-200 bg-red-50',
            badge: 'bg-red-100 text-red-700 ring-red-200',
            icon: 'bg-red-100 text-red-700',
        },

        neutral: {
            wrapper: 'border-gray-200 bg-gray-50',
            badge: 'bg-gray-100 text-gray-700 ring-gray-200',
            icon: 'bg-gray-100 text-gray-700',
        },
    }[tone] ?? {
        wrapper: 'border-gray-200 bg-gray-50',
        badge: 'bg-gray-100 text-gray-700 ring-gray-200',
        icon: 'bg-gray-100 text-gray-700',
    }
})

const recommendationIcon = computed(() => {
    return {
        strong_hire: '✓',
        hire: '✓',
        consider: '!',
        weak: '↓',
        no_hire: '×',
    }[props.scorecard?.recommendation?.key] ?? '•'
})

const scorePercentage = (average) => {
    if (average === null || average === undefined) {
        return 0
    }

    return Math.min(
        100,
        Math.max(0, (Number(average) / 5) * 100)
    )
}

const scoreBarClass = (average) => {
    const score = Number(average)

    if (score >= 4.5) {
        return 'bg-green-500'
    }

    if (score >= 3.75) {
        return 'bg-emerald-500'
    }

    if (score >= 3) {
        return 'bg-amber-500'
    }

    if (score >= 2) {
        return 'bg-orange-500'
    }

    return 'bg-red-500'
}

const scoreTextClass = (average) => {
    const score = Number(average)

    if (score >= 4.5) {
        return 'text-green-700'
    }

    if (score >= 3.75) {
        return 'text-emerald-700'
    }

    if (score >= 3) {
        return 'text-amber-700'
    }

    if (score >= 2) {
        return 'text-orange-700'
    }

    return 'text-red-700'
}

const scoreBackgroundClass = (average) => {
    const score = Number(average)

    if (score >= 4.5) {
        return 'bg-green-50 border-green-100'
    }

    if (score >= 3.75) {
        return 'bg-emerald-50 border-emerald-100'
    }

    if (score >= 3) {
        return 'bg-amber-50 border-amber-100'
    }

    if (score >= 2) {
        return 'bg-orange-50 border-orange-100'
    }

    return 'bg-red-50 border-red-100'
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

const starItems = (average) => {
    const score = Number(average ?? 0)

    return Array.from({ length: 5 }, (_, index) => {
        const starNumber = index + 1

        if (score >= starNumber) {
            return 'full'
        }

        if (score >= starNumber - 0.5) {
            return 'half'
        }

        return 'empty'
    })
}

const categoryInitials = (label) => {
    if (!label) {
        return 'SC'
    }

    return label
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase()
}
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 px-5 py-4"
        >
            <div>
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-lg"
                    >
                        📊
                    </div>

                    <div>
                        <h2 class="font-semibold text-gray-800">
                            Interview Scorecard
                        </h2>

                        <p class="mt-0.5 text-xs text-gray-400">
                            Consolidated candidate evaluation
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="scorecard?.completed_rounds"
                class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600"
            >
                {{ scorecard.completed_rounds }}
                {{
                    scorecard.completed_rounds === 1
                        ? 'round completed'
                        : 'rounds completed'
                }}
            </div>
        </div>

        <!-- No scorecard data -->
        <div
            v-if="!hasScorecardData"
            class="px-6 py-10 text-center"
        >
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-xl"
            >
                ☆
            </div>

            <p class="mt-3 text-sm font-medium text-gray-600">
                No scorecard available yet
            </p>

            <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-gray-400">
                The scorecard will appear after interview rounds
                containing rated questions are completed.
            </p>
        </div>

        <div
            v-else
            class="space-y-6 p-5"
        >
            <!-- Overall score and recommendation -->
            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-5"
            >
                <!-- Overall average -->
                <div
                    class="relative overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-5 md:col-span-2"
                >
                    <div
                        class="absolute -right-5 -top-5 h-24 w-24 rounded-full bg-indigo-100/50"
                    />

                    <div class="relative">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-indigo-500"
                        >
                            Overall Score
                        </p>

                        <div class="mt-3 flex items-end gap-2">
                            <span
                                class="text-4xl font-bold text-gray-900"
                            >
                                {{
                                    formatScore(
                                        scorecard.overall_average
                                    )
                                }}
                            </span>

                            <span
                                class="mb-1 text-sm font-medium text-gray-400"
                            >
                                / 5
                            </span>
                        </div>

                        <div class="mt-3 flex items-center gap-1">
                            <span
                                v-for="(star, index) in starItems(
                                    scorecard.overall_average
                                )"
                                :key="index"
                                :class="[
                                    'text-lg leading-none',
                                    star === 'full'
                                        ? 'text-yellow-400'
                                        : star === 'half'
                                          ? 'text-yellow-300'
                                          : 'text-gray-200',
                                ]"
                            >
                                ★
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-gray-500">
                            Based on
                            <strong class="text-gray-700">
                                {{
                                    scorecard.total_rated_questions ??
                                    0
                                }}
                            </strong>
                            rated
                            {{
                                scorecard.total_rated_questions === 1
                                    ? 'question'
                                    : 'questions'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Recommendation -->
                <div
                    v-if="scorecard.recommendation"
                    :class="[
                        'rounded-xl border p-5 md:col-span-3',
                        recommendationClasses.wrapper,
                    ]"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                :class="[
                                    'flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-lg font-bold',
                                    recommendationClasses.icon,
                                ]"
                            >
                                {{ recommendationIcon }}
                            </div>

                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    System Recommendation
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold text-gray-900"
                                >
                                    {{
                                        scorecard.recommendation
                                            .label
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs leading-5 text-gray-500"
                                >
                                    Generated from the candidate's
                                    completed and rated interview
                                    responses.
                                </p>
                            </div>
                        </div>

                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-semibold ring-1',
                                recommendationClasses.badge,
                            ]"
                        >
                            {{
                                formatScore(
                                    scorecard.overall_average
                                )
                            }}
                            / 5
                        </span>
                    </div>

                    <div
                        class="mt-4 h-2 overflow-hidden rounded-full bg-white/80"
                    >
                        <div
                            :class="[
                                'h-full rounded-full transition-all duration-500',
                                scoreBarClass(
                                    scorecard.overall_average
                                ),
                            ]"
                            :style="{
                                width:
                                    scorePercentage(
                                        scorecard.overall_average
                                    ) + '%',
                            }"
                        />
                    </div>

                    <p class="mt-2 text-right text-xs text-gray-400">
                        {{
                            Math.round(
                                scorePercentage(
                                    scorecard.overall_average
                                )
                            )
                        }}%
                    </p>
                </div>
            </div>

            <!-- Category scores -->
            <div v-if="scorecard.categories?.length">
                <div
                    class="mb-3 flex flex-wrap items-center justify-between gap-3"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">
                            Category Performance
                        </h3>

                        <p class="mt-0.5 text-xs text-gray-400">
                            Average ratings grouped by evaluation area
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-600"
                    >
                        {{ scorecard.categories.length }}
                        categories
                    </span>
                </div>

                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                >
                    <div
                        v-for="category in scorecard.categories"
                        :key="category.key"
                        class="rounded-xl border border-gray-100 bg-gray-50/70 p-4"
                    >
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold text-indigo-600 shadow-sm ring-1 ring-gray-100"
                                >
                                    {{
                                        categoryInitials(
                                            category.label
                                        )
                                    }}
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-gray-800"
                                    >
                                        {{ category.label }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-xs text-gray-400"
                                    >
                                        {{
                                            category.responses_count
                                        }}
                                        rated
                                        {{
                                            category.responses_count ===
                                            1
                                                ? 'response'
                                                : 'responses'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right">
                                <p
                                    :class="[
                                        'text-lg font-bold',
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
                                </p>

                                <p class="text-xs text-gray-400">
                                    / 5
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200"
                        >
                            <div
                                :class="[
                                    'h-full rounded-full transition-all duration-500',
                                    scoreBarClass(
                                        category.average
                                    ),
                                ]"
                                :style="{
                                    width:
                                        scorePercentage(
                                            category.average
                                        ) + '%',
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Round performance -->
            <div v-if="scorecard.rounds?.length">
                <div
                    class="mb-3 flex flex-wrap items-center justify-between gap-3"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">
                            Round Performance
                        </h3>

                        <p class="mt-0.5 text-xs text-gray-400">
                            Candidate's score across completed rounds
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-gray-100"
                >
                    <div
                        v-for="(round, index) in scorecard.rounds"
                        :key="round.progress_id ?? round.round_id"
                        :class="[
                            'flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between',
                            index !== scorecard.rounds.length - 1
                                ? 'border-b border-gray-100'
                                : '',
                        ]"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600"
                            >
                                {{ index + 1 }}
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-gray-800"
                                >
                                    {{ round.round_name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-gray-400"
                                >
                                    {{ round.rated_questions ?? 0 }}
                                    rated
                                    {{
                                        round.rated_questions === 1
                                            ? 'question'
                                            : 'questions'
                                    }}

                                    <template
                                        v-if="
                                            round.overall_rating !==
                                                null &&
                                            round.overall_rating !==
                                                undefined
                                        "
                                    >
                                        · Interviewer rating:
                                        {{
                                            formatScore(
                                                round.overall_rating
                                            )
                                        }}
                                    </template>
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-3 sm:w-56"
                        >
                            <div
                                class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"
                            >
                                <div
                                    :class="[
                                        'h-full rounded-full',
                                        scoreBarClass(
                                            round.average ??
                                                round.overall_rating
                                        ),
                                    ]"
                                    :style="{
                                        width:
                                            scorePercentage(
                                                round.average ??
                                                    round.overall_rating
                                            ) + '%',
                                    }"
                                />
                            </div>

                            <div
                                :class="[
                                    'min-w-[70px] rounded-lg border px-2.5 py-1.5 text-center',
                                    scoreBackgroundClass(
                                        round.average ??
                                            round.overall_rating
                                    ),
                                ]"
                            >
                                <span
                                    :class="[
                                        'text-sm font-bold',
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
                                </span>

                                <span
                                    class="ml-0.5 text-xs text-gray-400"
                                >
                                    /5
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disclaimer -->
            <div
                class="flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5"
            >
                <span class="mt-0.5 text-sm">ℹ️</span>

                <p class="text-xs leading-5 text-blue-700">
                    This recommendation is calculated from interview
                    ratings and should support, not replace, the final
                    hiring decision.
                </p>
            </div>
        </div>
    </section>
</template>
