<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { ref, computed } from "vue";

const page = usePage();

const props = defineProps({
    candidate: { type: Object, default: null },
    requirements: { type: Array, default: () => [] },
    missingRequired: { type: Array, default: () => [] },
});

const email = ref("");
const submitting = ref(false);
const errors = ref({});

const lookup = () => {
    submitting.value = true;
    errors.value = {};
    router.post(
        route("candidate.documents.lookup"),
        { email: email.value },
        {
            preserveScroll: true,
            onError: (e) => { errors.value = e; submitting.value = false; },
            onSuccess: () => { submitting.value = false; },
    
        }
    );
};

const onFileChange = (event, req) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("document_requirement_id", req.id);
    formData.append("document", file);

    router.post(
        route("candidate.documents.store", props.candidate.id),
        formData,
        {
            preserveScroll: true,
            headers: { "Content-Type": "multipart/form-data" },
            onError: (e) => { errors.value = e; },
            onSuccess: () => { event.target.value = ""; },
        }
    );
};

const missingSet = computed(() => new Set(props.missingRequired));

const statusFor = (req) => {
    if (!req.existing) return "missing";
    return req.existing.is_verified ? "verified" : "pending";
};

const statusLabel = (req) => {
    const s = statusFor(req);
    if (s === "verified") return "Verified";
    if (s === "pending") return "Pending Review";
    return "Not Uploaded";
};

const statusClass = (req) => {
    const s = statusFor(req);
    if (s === "verified") return "bg-green-100 text-green-700";
    if (s === "pending") return "bg-yellow-100 text-yellow-700";
    return "bg-gray-100 text-gray-600";
};

const allRequiredDone = computed(() => props.missingRequired.length === 0);

const removeDoc = (req) => {
    if (!confirm("Remove this document?")) return;
    router.delete(
        route("candidate.documents.destroy", [props.candidate.id, req.existing.id]),
        { preserveScroll: true }
    );
};
</script>

<template>
    <PublicLayout>
        <Head title="Document Upload" />
        <div class="mx-auto max-w-3xl space-y-6 py-8">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-ink">Document Verification</h1>
                <p class="mt-1 text-sm text-ink-mute">
                    Search by your email address to upload required documents before your offer letter is released.
                </p>
            </div>

            <!-- Lookup form -->
            <div v-if="!candidate" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="lookup" class="space-y-4">
                    <div>
                        <label class="label">Email Address <span class="text-red-500">*</span></label>
                        <input v-model="email" type="email" class="input-field" placeholder="you@example.com" />
                        <p v-if="errors.email" class="error">{{ errors.email }}</p>
                    </div>
                    <button type="submit" :disabled="submitting" class="btn-primary w-full">
                        {{ submitting ? 'Searching...' : 'Find My Application' }}
                    </button>
                </form>
            </div>

            <!-- Candidate view -->
            <div v-else class="space-y-6">
                <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-700">Found application for</p>
                            <p class="text-lg font-semibold text-green-900">{{ candidate.name }}</p>
                            <p class="text-sm text-green-700">{{ candidate.email }}</p>
                        </div>
                        <div v-if="allRequiredDone" class="rounded-full bg-green-200 px-4 py-2 text-sm font-medium text-green-800">
                            All Required Documents Verified
                        </div>
                        <div v-else class="rounded-full bg-yellow-200 px-4 py-2 text-sm font-medium text-yellow-800">
                            Documents Pending
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Document</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="req in requirements" :key="req.id">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-ink">{{ req.name }}</div>
                                    <div v-if="req.description" class="text-sm text-ink-mute">{{ req.description }}</div>
                                    <span v-if="req.is_required" class="mt-1 inline-block text-xs font-medium text-red-600">Required</span>
                                    <span v-else class="mt-1 inline-block text-xs font-medium text-gray-400">Optional</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', statusClass(req)]">
                                        {{ statusLabel(req) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <template v-if="req.is_submitted && req.existing?.file_path">
                                        <a :href="/storage/ + req.existing.file_path" target="_blank" class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </template>
                                    <label v-if="req.is_active" class="ml-3 cursor-pointer text-indigo-600 hover:text-indigo-900">
                                        {{ req.is_submitted ? 'Replace' : 'Upload' }}
                                        <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, req)" />
                                    </label>
                                    <button v-if="req.is_submitted" @click="removeDoc(req)" class="ml-3 text-red-600 hover:text-red-900">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="!allRequiredDone" class="text-center text-sm text-ink-mute">
                    Required documents: {{ missingRequired.join(', ') }}
                </p>
            </div>
        </div>
    </PublicLayout>
</template>