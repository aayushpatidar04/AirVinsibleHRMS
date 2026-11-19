<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ batches: Array });

const form = useForm({ file: null });
const submit = () => form.post(route("workforce.attendance.import.store"), { forceFormData: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Import machine punches" />

    <AppLayout>
        <PageHeader title="Import machine punches" subtitle="Upload an export from the biometric / attendance machine. Re-uploading the same file is safe: duplicates are skipped." />

        <div class="mb-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <form class="flex flex-wrap items-end gap-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700">CSV file</label>
                    <input type="file" accept=".csv,.txt" required class="mt-1 block text-sm" @input="form.file = $event.target.files[0]" />
                    <p v-if="form.errors.file" class="mt-1 text-xs text-red-600">{{ form.errors.file }}</p>
                </div>
                <button type="submit" :disabled="form.processing || !form.file" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-50">Import</button>
            </form>
            <div class="mt-5 rounded-xl bg-gray-50 p-4 text-xs text-gray-600">
                <p class="font-semibold text-gray-700">File format</p>
                <p class="mt-1">First row is the header. Columns (any order): <code>employee_id</code>, <code>punched_at</code> (or <code>date</code> + <code>time</code>), optional <code>direction</code> (in / out) and <code>device_id</code>. Without a direction, punches alternate in / out.</p>
                <p class="mt-1">Employee IDs that do not match anyone are reported below, not dropped silently.</p>
            </div>
        </div>

        <h2 class="mb-2 text-sm font-semibold text-gray-800">Recent imports</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">File</th><th class="px-4 py-3">By</th><th class="px-4 py-3">Rows</th><th class="px-4 py-3">Imported</th><th class="px-4 py-3">Duplicates</th><th class="px-4 py-3">Unmatched</th><th class="px-4 py-3">Errors</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="b in batches" :key="b.id" class="align-top">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ b.filename }}<p class="text-xs font-normal text-gray-400">{{ b.at }}</p></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ b.uploaded_by }}</td>
                        <td class="px-4 py-2.5">{{ b.total }}</td>
                        <td class="px-4 py-2.5 text-emerald-700">{{ b.imported }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ b.duplicates }}</td>
                        <td class="px-4 py-2.5" :class="b.unmatched ? 'text-orange-700' : 'text-gray-500'">
                            {{ b.unmatched }}
                            <p v-if="b.details?.unmatched_employee_ids?.length" class="text-xs text-gray-400">{{ b.details.unmatched_employee_ids.join(", ") }}</p>
                        </td>
                        <td class="px-4 py-2.5" :class="b.errors ? 'text-red-700' : 'text-gray-500'">{{ b.errors }}</td>
                    </tr>
                    <tr v-if="!batches.length"><td colspan="7" class="px-4 py-8 text-center text-gray-500">No imports yet.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
