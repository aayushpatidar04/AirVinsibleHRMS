<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm, Link } from "@inertiajs/vue3";

const props = defineProps({ nextSequence: Number });

const form = useForm({
    name: "",
    sequence_number: props.nextSequence,
    description: "",
    is_mandatory: true,
    is_hr_round: false,
    is_ops_round: false,
});

const submit = () => form.post(route("admin.rounds.store"));
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.rounds.index')"
                    class="text-gray-400 hover:text-gray-600"
                    >←</Link
                >
                <h1 class="text-2xl font-bold text-gray-900">
                    New Interview Round
                </h1>
            </div>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label"
                            >Round Name
                            <span class="text-red-500">*</span></label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="input-field"
                            placeholder="e.g. First Round, HR Round"
                        />
                        <p v-if="form.errors.name" class="error">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label class="label"
                            >Sequence Number
                            <span class="text-red-500">*</span></label
                        >
                        <input
                            v-model.number="form.sequence_number"
                            type="number"
                            class="input-field"
                            min="1"
                        />
                        <p v-if="form.errors.sequence_number" class="error">
                            {{ form.errors.sequence_number }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="label">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="input-field"
                        placeholder="Describe this round..."
                    />
                </div>

                <div class="space-y-3">
                    <label class="label">Round Type</label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_mandatory"
                            type="checkbox"
                            class="rounded text-indigo-600"
                        />
                        <span class="text-sm text-gray-700"
                            >Mandatory round (all candidates must attend)</span
                        >
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_hr_round"
                            type="checkbox"
                            class="rounded text-orange-500"
                            @change="
                                form.is_ops_round = form.is_hr_round
                                    ? false
                                    : form.is_ops_round
                            "
                        />
                        <span class="text-sm text-gray-700">
                            <span class="font-medium text-orange-700"
                                >HR Round</span
                            >
                            — Salary mandatory for TL/QA/AM/OM and Other
                            profiles
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_ops_round"
                            type="checkbox"
                            class="rounded text-blue-500"
                            @change="
                                form.is_hr_round = form.is_ops_round
                                    ? false
                                    : form.is_hr_round
                            "
                        />
                        <span class="text-sm text-gray-700">
                            <span class="font-medium text-blue-700"
                                >OPS Round</span
                            >
                            — Capture an optional salary range for
                            Advisor/Executive profiles
                        </span>
                    </label>
                </div>

                <!-- Salary hint box -->
                <div
                    v-if="form.is_hr_round || form.is_ops_round"
                    class="rounded-lg p-4 text-sm"
                    :class="
                        form.is_hr_round
                            ? 'bg-orange-50 border border-orange-200 text-orange-800'
                            : 'bg-blue-50 border border-blue-200 text-blue-800'
                    "
                >
                    <p v-if="form.is_hr_round">
                        <strong>💰 HR Round:</strong> Final salary offer is
                        <strong>mandatory</strong> for TL/QA/AM/OM and Other
                        profiles. Advisor/Executive profiles may receive a
                        salary range preview from OPS.
                    </p>
                    <p v-if="form.is_ops_round">
                        <strong>📊 OPS Round:</strong> Record a salary range for
                        Advisor/Executive profiles if available.
                    </p>
                    <div class="flex justify-end gap-3 pt-2 border-t">
                        <Link
                            :href="route('admin.rounds.index')"
                            class="btn-secondary"
                            >Cancel</Link
                        >
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn-primary"
                        >
                            {{
                                form.processing ? "Creating..." : "Create Round"
                            }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
