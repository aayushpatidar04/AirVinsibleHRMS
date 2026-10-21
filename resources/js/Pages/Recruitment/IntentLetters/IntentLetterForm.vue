<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ArrowLeft, FileCheck2, LoaderCircle } from "lucide-vue-next";

const props = defineProps({
    mode: { type: String, required: true },
    candidate: { type: Object, required: true },
    defaults: { type: Object, required: true },
    routes: { type: Object, required: true },
});

const form = useForm({
    position_title: props.defaults.position_title ?? "",
    department: props.defaults.department ?? "",
    reporting_to: props.defaults.reporting_to ?? "",
    work_location: props.defaults.work_location ?? "",
    monthly_ctc: props.defaults.monthly_ctc ?? "",
    monthly_in_hand: props.defaults.monthly_in_hand ?? "",
    joining_date: props.defaults.joining_date ?? "",
});

function submit() {
    if (props.mode === "edit") {
        form.put(props.routes.update);
        return;
    }

    form.post(props.routes.store);
}
</script>

<template>
    <main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <Link
            :href="routes.back"
            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
        >
            <ArrowLeft class="h-4 w-4" />
            Back
        </Link>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
                    Candidate: {{ candidate.name }} · {{ candidate.email }}
                </p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    {{ mode === "edit" ? "Edit" : "Prepare" }} Letter of Intent
                </h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    These details are used to create the company LOI PDF. Review monthly values and the proposed joining date before sending it.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="position_title" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Position offered
                    </label>
                    <input
                        id="position_title"
                        v-model="form.position_title"
                        type="text"
                        required
                        maxlength="255"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.position_title" class="mt-1 text-sm text-red-600">
                        {{ form.errors.position_title }}
                    </p>
                </div>

                <div>
                    <label for="department" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Department
                    </label>
                    <input
                        id="department"
                        v-model="form.department"
                        type="text"
                        required
                        maxlength="255"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.department" class="mt-1 text-sm text-red-600">
                        {{ form.errors.department }}
                    </p>
                </div>

                <div>
                    <label for="reporting_to" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Reporting To
                    </label>
                    <input
                        id="reporting_to"
                        v-model="form.reporting_to"
                        type="text"
                        required
                        maxlength="255"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.reporting_to" class="mt-1 text-sm text-red-600">
                        {{ form.errors.reporting_to }}
                    </p>
                </div>

                <div>
                    <label for="work_location" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Work location
                    </label>
                    <input
                        id="work_location"
                        v-model="form.work_location"
                        type="text"
                        required
                        maxlength="255"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.work_location" class="mt-1 text-sm text-red-600">
                        {{ form.errors.work_location }}
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="monthly_ctc" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Monthly offered CTC (₹)
                        </label>
                        <input
                            id="monthly_ctc"
                            v-model="form.monthly_ctc"
                            type="number"
                            required
                            min="0"
                            step="0.01"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.monthly_ctc" class="mt-1 text-sm text-red-600">
                            {{ form.errors.monthly_ctc }}
                        </p>
                    </div>

                    <div>
                        <label for="monthly_in_hand" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Estimated monthly in-hand (₹)
                        </label>
                        <input
                            id="monthly_in_hand"
                            v-model="form.monthly_in_hand"
                            type="number"
                            required
                            min="0"
                            step="0.01"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.monthly_in_hand" class="mt-1 text-sm text-red-600">
                            {{ form.errors.monthly_in_hand }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="joining_date" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Proposed date of joining
                    </label>
                    <input
                        id="joining_date"
                        v-model="form.joining_date"
                        type="date"
                        required
                        :min="new Date().toISOString().slice(0, 10)"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.joining_date" class="mt-1 text-sm text-red-600">
                        {{ form.errors.joining_date }}
                    </p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    The candidate will acknowledge this preliminary Letter of Intent. It does not replace the formal offer letter.
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                    <Link
                        :href="routes.back"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <FileCheck2 v-else class="h-4 w-4" />
                        Save draft and preview
                    </button>
                </div>
            </form>
        </section>
    </main>
</template>
