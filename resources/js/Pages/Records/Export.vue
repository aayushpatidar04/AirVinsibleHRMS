<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    summary: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <Head title="Export HRMS Records" />

        <div class="mx-auto max-w-4xl space-y-6">
            <header>
                <h1 class="font-display text-2xl font-bold tracking-tight">
                    Export HRMS Records
                </h1>
                <p class="mt-1 text-sm text-ink-soft">
                    Download a ZIP archive of employee and candidate records with their available files.
                </p>
            </header>

            <section class="surface space-y-5 p-6">
                <div>
                    <h2 class="font-display text-base font-bold">Included in the archive</h2>
                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-ink-soft">
                        <li>Employee details in <code>employees.csv</code> (credentials and authentication tokens are excluded).</li>
                        <li>All candidate fields in <code>candidates.csv</code>.</li>
                        <li>Uploaded candidate documents, resumes and registration-form files, employee avatars, and generated offer/intent-letter PDFs.</li>
                        <li><code>documents.csv</code> maps each file to its record and notes files that are no longer available.</li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-mute">Employees</p>
                        <p class="mt-1 font-display text-2xl font-bold text-indigo-700">{{ summary.employees }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-100 bg-violet-50/50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-mute">Candidates</p>
                        <p class="mt-1 font-display text-2xl font-bold text-violet-700">{{ summary.candidates }}</p>
                    </div>
                    <div class="rounded-xl border border-teal-100 bg-teal-50/50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-mute">Candidate documents</p>
                        <p class="mt-1 font-display text-2xl font-bold text-teal-700">{{ summary.candidate_documents }}</p>
                    </div>
                </div>

                <div class="flex flex-col justify-between gap-4 border-t border-indigo-50 pt-5 sm:flex-row sm:items-center">
                    <p class="max-w-xl text-xs leading-relaxed text-amber-700">
                        This export contains confidential employee and candidate information. Handle and store it securely.
                    </p>
                    <a
                        :href="route('records.export.download')"
                        class="btn-primary inline-flex shrink-0 items-center justify-center gap-2"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l4-4m-4 4l-4-4M5 17v3h14v-3" />
                        </svg>
                        Download ZIP
                    </a>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
