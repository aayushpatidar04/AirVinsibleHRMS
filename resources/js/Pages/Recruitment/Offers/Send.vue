<script setup>
import {
    ArrowLeft,
    CheckCircle2,
    FileText,
    LoaderCircle,
    Mail,
    Plus,
    Send,
    Trash2,
} from "lucide-vue-next";

import { Head, Link, useForm } from "@inertiajs/vue3";

import AppLayout from "@/Layouts/AppLayout.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    offer: {
        type: Object,
        required: true,
    },

    candidate: {
        type: Object,
        required: true,
    },

    defaults: {
        type: Object,
        required: true,
    },

    routes: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    recipient_email: props.defaults.recipient_email ?? "",

    cc: props.defaults.cc?.length ? [...props.defaults.cc] : [],

    subject: props.defaults.subject ?? "",

    message: props.defaults.message ?? "",

    attach_pdf: props.defaults.attach_pdf ?? true,
});

function addCc() {
    form.cc.push("");
}

function removeCc(index) {
    form.cc.splice(index, 1);
}

function submit() {
    form.post(props.routes.send, {
        preserveScroll: true,
    });
}

function formatDate(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
}
</script>

<template>
    <Head :title="`Send ${offer.offer_number}`" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Link
                        :href="routes.show"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-600 shadow-sm transition hover:bg-gray-50"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </Link>

                    <div class="min-w-0">
                        <h1
                            class="truncate text-xl font-semibold text-gray-900"
                        >
                            Send Offer Letter
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ offer.offer_number }}
                            · Version
                            {{ offer.version ?? 1 }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="offer.send_count"
                    class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
                >
                    Sent
                    {{ offer.send_count }}
                    {{ offer.send_count === 1 ? "time" : "times" }}
                </div>
            </div>
        </header>

        <main
            class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <section
                    v-if="Object.keys(form.errors).length"
                    class="rounded-2xl border border-red-200 bg-red-50 p-5"
                >
                    <h2 class="font-semibold text-red-900">
                        Offer could not be sent
                    </h2>

                    <ul class="mt-3 space-y-1 text-sm text-red-700">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </section>

                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"
                            >
                                <Mail class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="font-semibold text-gray-900">
                                    Email Details
                                </h2>

                                <p class="mt-0.5 text-sm text-gray-500">
                                    Review the recipient and email content
                                    before sending.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 px-5 py-5 sm:px-6">
                        <div>
                            <label
                                for="recipient_email"
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Candidate Email
                                <span class="text-red-500"> * </span>
                            </label>

                            <input
                                id="recipient_email"
                                v-model="form.recipient_email"
                                type="email"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{
                                    'border-red-400':
                                        form.errors.recipient_email,
                                }"
                            />

                            <p
                                v-if="form.errors.recipient_email"
                                class="mt-1.5 text-sm text-red-600"
                            >
                                {{ form.errors.recipient_email }}
                            </p>
                        </div>

                        <div>
                            <div
                                class="mb-2 flex items-center justify-between gap-3"
                            >
                                <label
                                    class="text-sm font-medium text-gray-700"
                                >
                                    CC Recipients
                                </label>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                    @click="addCc"
                                >
                                    <Plus class="h-4 w-4" />

                                    Add CC
                                </button>
                            </div>

                            <div v-if="form.cc.length" class="space-y-2">
                                <div
                                    v-for="(email, index) in form.cc"
                                    :key="index"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        v-model="form.cc[index]"
                                        type="email"
                                        class="block min-w-0 flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="manager@example.com"
                                    />

                                    <button
                                        type="button"
                                        class="rounded-lg p-2.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Remove CC"
                                        @click="removeCc(index)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <p
                                v-if="form.errors.cc"
                                class="mt-1.5 text-sm text-red-600"
                            >
                                {{ form.errors.cc }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="subject"
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Subject
                                <span class="text-red-500"> * </span>
                            </label>

                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <p
                                v-if="form.errors.subject"
                                class="mt-1.5 text-sm text-red-600"
                            >
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="message"
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Message
                                <span class="text-red-500"> * </span>
                            </label>

                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="11"
                                class="block w-full resize-y rounded-xl border-gray-300 text-sm leading-6 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <p
                                v-if="form.errors.message"
                                class="mt-1.5 text-sm text-red-600"
                            >
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <label
                            class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4"
                        >
                            <input
                                v-model="form.attach_pdf"
                                type="checkbox"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />

                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Attach offer-letter PDF
                                </span>

                                <span class="mt-1 block text-sm text-gray-500">
                                    The latest PDF will be generated
                                    automatically when it does not already
                                    exist.
                                </span>
                            </span>
                        </label>
                    </div>
                </section>

                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <Link
                        :href="routes.show"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        <LoaderCircle
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />

                        <Send v-else class="h-4 w-4" />

                        {{ offer.send_count ? "Resend Offer" : "Send Offer" }}
                    </button>
                </div>
            </form>

            <aside class="space-y-6">
                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">
                            Offer Summary
                        </h2>
                    </div>

                    <dl class="divide-y divide-gray-100">
                        <div class="px-5 py-3.5">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Candidate
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ candidate.name }}
                            </dd>
                        </div>

                        <div class="px-5 py-3.5">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Designation
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ offer.designation ?? "—" }}
                            </dd>
                        </div>

                        <div class="px-5 py-3.5">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Department
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ offer.department ?? "—" }}
                            </dd>
                        </div>

                        <div class="px-5 py-3.5">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Joining Date
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ formatDate(offer.joining_date) }}
                            </dd>
                        </div>

                        <div class="px-5 py-3.5">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Valid Till
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ formatDate(offer.valid_till) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                        />

                        <div>
                            <p class="font-semibold text-emerald-900">
                                Ready to send
                            </p>

                            <p class="mt-1 text-sm leading-5 text-emerald-700">
                                Sending this email changes the offer status to
                                Sent and records the recipient, message and
                                sender.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="form.attach_pdf"
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                >
                    <div class="flex items-start gap-3">
                        <FileText
                            class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"
                        />

                        <div>
                            <p class="font-semibold text-blue-900">
                                PDF attachment
                            </p>

                            <p class="mt-1 text-sm leading-5 text-blue-700">
                                {{
                                    offer.pdf_available
                                        ? "The generated offer PDF will be attached."
                                        : "A fresh offer PDF will be generated before sending."
                                }}
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </main>
    </div>
</template>