<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { CheckCircle2, Download, FileCheck2, LoaderCircle } from "lucide-vue-next";

const props = defineProps({
    company: { type: Object, required: true },
    candidate: { type: Object, required: true },
    letter: { type: Object, required: true },
    portal: { type: Object, required: true },
});

const form = useForm({
    candidate_name: props.candidate.name ?? "",
    consent: false,
});

const declineForm = useForm({
    candidate_name: props.candidate.name ?? "",
    reason: "",
    confirmation: false,
});

const acknowledgedAt = computed(() => {
    if (!props.letter.acknowledged_at) return "";
    const date = new Date(props.letter.acknowledged_at);
    return Number.isNaN(date.getTime())
        ? props.letter.acknowledged_at
        : new Intl.DateTimeFormat("en-IN", { dateStyle: "long", timeStyle: "short" }).format(date);
});

const rejectedAt = computed(() => {
    if (!props.letter.rejected_at) return "";
    const date = new Date(props.letter.rejected_at);
    return Number.isNaN(date.getTime())
        ? props.letter.rejected_at
        : new Intl.DateTimeFormat("en-IN", { dateStyle: "long", timeStyle: "short" }).format(date);
});

function formatDate(value) {
    if (!value) return "—";
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("en-IN", { day: "2-digit", month: "long", year: "numeric" }).format(date);
}

function formatMoney(value) {
    return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }).format(Number(value ?? 0));
}

function acknowledge() {
    form.post(props.portal.routes.acknowledge, { preserveScroll: true });
}

function decline() {
    declineForm.post(props.portal.routes.decline, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Letter of Intent ${letter.letter_number}`" />

    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6">
                <div>
                    <p class="text-lg font-bold text-slate-900">{{ company.name }}</p>
                    <p class="text-sm text-slate-500">Private and confidential · Letter of Intent</p>
                </div>
                <a
                    :href="portal.routes.download"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <Download class="h-4 w-4" /> Download LOI PDF
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-6 px-4 py-7 sm:px-6">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-700 to-violet-700 px-6 py-8 text-white shadow-lg sm:px-9">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-200">Letter of Intent</p>
                <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold">Dear {{ candidate.name }},</h1>
                        <p class="mt-2 max-w-2xl text-indigo-100">
                            Following our discussion and the selection process, we are pleased to share your proposed employment terms.
                        </p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">
                        <p class="text-xs uppercase text-indigo-200">Letter number</p>
                        <p class="mt-1 font-bold">{{ letter.letter_number }}</p>
                    </div>
                </div>
            </section>

            <section v-if="portal.acknowledged" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-6 w-6 shrink-0 text-emerald-700" />
                    <div>
                        <h2 class="font-bold">Acknowledgement recorded</h2>
                        <p class="mt-1 text-sm leading-6">
                            This Letter of Intent was electronically signed by
                            <strong>{{ letter.acknowledgement_name }}</strong>
                            on {{ acknowledgedAt }}. You can download the acknowledged copy above.
                        </p>
                    </div>
                </div>
            </section>

            <section v-if="portal.declined" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900">
                <div class="flex items-start gap-3">
                    <FileCheck2 class="mt-0.5 h-6 w-6 shrink-0 text-red-700" />
                    <div>
                        <h2 class="font-bold">Your decision has been sent to HR</h2>
                        <p class="mt-1 text-sm leading-6">
                            This Letter of Intent was declined by
                            <strong>{{ letter.rejection_name }}</strong>
                            on {{ rejectedAt }}.
                        </p>
                        <p class="mt-2 text-sm font-semibold">Your remark</p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ letter.rejection_remark }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4">
                    <h2 class="text-lg font-bold text-slate-900">Proposed employment terms</h2>
                    <span class="text-sm text-slate-500">Date: {{ formatDate(letter.letter_date) }}</span>
                </div>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Candidate</p><p class="mt-1 font-medium text-slate-900">{{ candidate.name }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p><p class="mt-1 break-all font-medium text-slate-900">{{ candidate.email }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Position offered</p><p class="mt-1 font-medium text-slate-900">{{ letter.position_title }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work location</p><p class="mt-1 font-medium text-slate-900">{{ letter.work_location }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Monthly offered CTC</p><p class="mt-1 font-medium text-slate-900">{{ formatMoney(letter.monthly_ctc) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estimated monthly in-hand</p><p class="mt-1 font-medium text-slate-900">{{ formatMoney(letter.monthly_in_hand) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proposed date of joining</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(letter.joining_date) }}</p></div>
                </div>
            </section>

            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <h2 class="font-bold text-slate-900">1. Joining Details</h2>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-6 text-slate-700">
                        <li>Your proposed employment will be with {{ company.name }}.</li>
                        <li>Your proposed work location is {{ letter.work_location }}.</li>
                        <li>Your monthly offered CTC is {{ formatMoney(letter.monthly_ctc) }}.</li>
                        <li>Your estimated monthly in-hand salary after applicable PF and ESIC deductions is {{ formatMoney(letter.monthly_in_hand) }}.</li>
                        <li>Salary and compensation details are confidential and should not be disclosed or discussed with any employee.</li>
                        <li>Your proposed Date of Joining is {{ formatDate(letter.joining_date) }}. The DOJ may be changed based on business requirements and will be communicated by the company, wherever applicable.</li>
                    </ul>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">2. Pre-Joining Conditions</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-700">This Letter of Intent is subject to successful completion of applicable pre-joining formalities, including required assessment/skill tests, HR and Operations selection, submission and verification of required documents, background verification wherever applicable, and fulfillment of eligibility criteria for the position. The final employment offer is subject to successful completion of these requirements.</p>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">3. Documents Required on DOJ</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Please bring your Aadhaar Card, PAN Card, two passport-size photographs, 10th and 12th Mark Sheets, Graduation Mark Sheets/Degree wherever applicable, Experience/Relieving Letter, last drawn salary proof, previous organization's offer/appointment letter, cancelled cheque or bank statement, and the last three months' salary slips from the previous organization.</p>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">4. General Terms &amp; Conditions</h2>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-6 text-slate-700">
                        <li>This letter is issued based on information and documents provided during recruitment.</li>
                        <li>Any discrepancy, misrepresentation, concealment, or adverse finding during verification may result in withdrawal of this Letter of Intent or termination of employment, as applicable.</li>
                        <li>Failure to report on the confirmed joining date without prior company approval may result in withdrawal of this letter.</li>
                        <li>Final employment terms are governed by the company's HR policies and the final appointment/offer letter.</li>
                        <li>This Letter of Intent does not override or replace company HR policies or final employment documentation.</li>
                    </ul>
                </div>
            </section>

            <section v-if="portal.can_acknowledge" class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-start gap-3">
                    <FileCheck2 class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600" />
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Employee declaration and acknowledgement</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            By acknowledging below, I confirm that I have read and understood the above terms and conditions and agree to comply with the applicable rules, policies, and requirements of {{ company.name }}.
                        </p>
                    </div>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="acknowledge">
                    <div>
                        <label for="signature-name" class="mb-1.5 block text-sm font-medium text-slate-700">Type your full name as your electronic signature</label>
                        <input
                            id="signature-name"
                            v-model="form.candidate_name"
                            type="text"
                            autocomplete="name"
                            required
                            minlength="2"
                            maxlength="255"
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.candidate_name" class="mt-1.5 text-sm text-red-600">{{ form.errors.candidate_name }}</p>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input
                            v-model="form.consent"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm leading-6 text-slate-700">
                            I acknowledge receipt of this Letter of Intent, confirm that I have read and understood its terms and employee declaration, and agree to comply with the applicable rules, policies, and requirements of {{ company.name }}. I understand that this Letter of Intent is preliminary and is not the final employment offer.
                        </span>
                    </label>
                    <p v-if="form.errors.consent" class="text-sm text-red-600">{{ form.errors.consent }}</p>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-60 sm:w-auto"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        I acknowledge this Letter of Intent
                    </button>
                </form>
            </section>

            <section v-if="portal.can_acknowledge" class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-bold text-slate-900">Do you want to decline this Letter of Intent?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    If the proposed terms do not work for you, submit a reason below. HR will be notified, and this response cannot be changed through this link.
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="decline">
                    <div>
                        <label for="decline-name" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Your full name
                        </label>
                        <input
                            id="decline-name"
                            v-model="declineForm.candidate_name"
                            type="text"
                            autocomplete="name"
                            required
                            minlength="2"
                            maxlength="255"
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                        />
                        <p v-if="declineForm.errors.candidate_name" class="mt-1.5 text-sm text-red-600">
                            {{ declineForm.errors.candidate_name }}
                        </p>
                    </div>

                    <div>
                        <label for="decline-reason" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Reason for declining
                        </label>
                        <textarea
                            id="decline-reason"
                            v-model="declineForm.reason"
                            rows="4"
                            required
                            minlength="3"
                            maxlength="2000"
                            placeholder="Please share why you are declining the proposed terms."
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                        />
                        <p v-if="declineForm.errors.reason" class="mt-1.5 text-sm text-red-600">
                            {{ declineForm.errors.reason }}
                        </p>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4">
                        <input
                            v-model="declineForm.confirmation"
                            type="checkbox"
                            required
                            class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500"
                        />
                        <span class="text-sm leading-6 text-slate-700">
                            I confirm that I am declining this Letter of Intent and authorize HR to record the reason above.
                        </span>
                    </label>
                    <p v-if="declineForm.errors.confirmation" class="text-sm text-red-600">
                        {{ declineForm.errors.confirmation }}
                    </p>

                    <button
                        type="submit"
                        :disabled="declineForm.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-300 bg-white px-5 py-3 text-sm font-bold text-red-700 hover:bg-red-50 disabled:opacity-60"
                    >
                        <LoaderCircle v-if="declineForm.processing" class="h-4 w-4 animate-spin" />
                        <FileCheck2 v-else class="h-4 w-4" />
                        Decline and send remark to HR
                    </button>
                </form>
            </section>

            <footer class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Warm regards,<br>HR Department · {{ company.name }}</p>
                <p v-if="company.email" class="mt-2">{{ company.email }}</p>
                <p v-if="company.phone" class="mt-1">{{ company.phone }}</p>
            </footer>
        </main>
    </div>
</template>
