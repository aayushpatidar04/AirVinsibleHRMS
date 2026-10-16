<script setup>
import { router, Link, Head } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, Download, FileText, LoaderCircle, Mail, Pencil, XCircle } from "lucide-vue-next";
import { ref } from "vue";

import AppLayout from "@/Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    candidate: { type: Object, required: true },
    letter: { type: Object, required: true },
    permissions: { type: Object, required: true },
    routes: { type: Object, required: true },
});

const processing = ref(false);

function sendLetter() {
    if (processing.value) return;
    processing.value = true;
    router.post(props.routes.send, {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
}

function cancelLetter() {
    if (!window.confirm("Cancel this Letter of Intent? The candidate's link will stop working.")) return;
    router.post(props.routes.cancel);
}

function formatDate(value) {
    if (!value) return "—";
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("en-IN", { day: "2-digit", month: "long", year: "numeric" }).format(date);
}

function formatDateTime(value) {
    if (!value) return "—";
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("en-IN", { dateStyle: "medium", timeStyle: "short" }).format(date);
}

function formatMoney(value) {
    return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }).format(Number(value ?? 0));
}

const statusLabel = {
    draft: "Draft",
    sent: "Sent",
    viewed: "Viewed",
    acknowledged: "Acknowledged",
    declined: "Declined by candidate",
    cancelled: "Cancelled",
};
</script>

<template>
    <Head :title="`Intent Letter ${letter.letter_number}`" />

    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Link :href="routes.candidate" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900">
                <ArrowLeft class="h-4 w-4" /> Candidate
            </Link>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-if="permissions.edit"
                    :href="routes.edit"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"
                >
                    <Pencil class="h-4 w-4" /> Edit draft
                </Link>
                <a
                    v-if="letter.pdf_available"
                    :href="routes.download"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"
                >
                    <Download class="h-4 w-4" /> Download PDF
                </a>
                <button
                    v-if="permissions.send"
                    type="button"
                    :disabled="processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    @click="sendLetter"
                >
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    <Mail v-else class="h-4 w-4" />
                    {{ letter.send_count ? "Resend to candidate" : "Send to candidate" }}
                </button>
                <button
                    v-if="permissions.cancel"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50"
                    @click="cancelLetter"
                >
                    <XCircle class="h-4 w-4" /> Cancel
                </button>
            </div>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Letter of Intent</p>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ letter.letter_number }}</h1>
                    <p class="mt-1 text-sm text-gray-500">For {{ candidate.name }} · {{ candidate.email }}</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
                    {{ statusLabel[letter.status] ?? letter.status }}
                </span>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-gray-500">Position offered</p><p class="mt-1 font-medium text-gray-900">{{ letter.position_title }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Work location</p><p class="mt-1 font-medium text-gray-900">{{ letter.work_location }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Proposed joining date</p><p class="mt-1 font-medium text-gray-900">{{ formatDate(letter.joining_date) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Monthly CTC</p><p class="mt-1 font-medium text-gray-900">{{ formatMoney(letter.monthly_ctc) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Monthly in-hand</p><p class="mt-1 font-medium text-gray-900">{{ formatMoney(letter.monthly_in_hand) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Letter date</p><p class="mt-1 font-medium text-gray-900">{{ formatDate(letter.letter_date) }}</p></div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900">Delivery and candidate response</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-gray-500">Sent to</p><p class="mt-1 text-sm text-gray-900">{{ letter.sent_to_email ?? "Not sent" }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Sent</p><p class="mt-1 text-sm text-gray-900">{{ formatDateTime(letter.sent_at) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-gray-500">Viewed</p><p class="mt-1 text-sm text-gray-900">{{ formatDateTime(letter.viewed_at) }}</p></div>
            </div>

            <div v-if="letter.acknowledged_at" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 text-emerald-700" />
                    <div>
                        <p class="font-semibold text-emerald-900">Candidate acknowledgement recorded</p>
                        <p class="mt-1 text-sm text-emerald-800">Electronically signed by {{ letter.acknowledgement_name }} on {{ formatDateTime(letter.acknowledged_at) }}.</p>
                        <p v-if="letter.acknowledgement_text" class="mt-2 text-sm leading-6 text-emerald-900">
                            “{{ letter.acknowledgement_text }}”
                        </p>
                        <a
                            v-if="letter.acknowledged_pdf_available"
                            :href="routes.download"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-900 underline"
                        >
                            <Download class="h-4 w-4" /> Download acknowledged copy
                        </a>
                    </div>
                </div>
            </div>
            <div v-else-if="letter.rejected_at" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <XCircle class="mt-0.5 h-5 w-5 text-red-700" />
                    <div>
                        <p class="font-semibold text-red-900">Candidate declined this Letter of Intent</p>
                        <p class="mt-1 text-sm text-red-800">
                            Submitted by {{ letter.rejection_name }} on {{ formatDateTime(letter.rejected_at) }}.
                        </p>
                        <p class="mt-3 text-sm font-semibold text-red-900">Candidate's remark</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-red-900">{{ letter.rejection_remark }}</p>
                    </div>
                </div>
            </div>
            <p v-else-if="letter.status === 'draft'" class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
                Review the PDF, then send it to {{ candidate.email }}. The formal offer remains unavailable until the candidate acknowledges this letter.
            </p>
            <p v-else-if="letter.status !== 'declined'" class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-900">
                Waiting for the candidate to review and acknowledge this Letter of Intent.
            </p>
        </section>

        <section v-if="permissions.create_offer" class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-emerald-900">Intent acknowledged — ready for formal offer</h2>
                <p class="mt-1 text-sm text-emerald-800">The candidate's acknowledgement is recorded. You can now prepare the formal employment offer.</p>
            </div>
            <Link :href="routes.create_offer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                <FileText class="h-4 w-4" /> Generate formal offer
            </Link>
        </section>
    </main>
</template>
