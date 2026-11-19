<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ periods: Object, clients: Array, filters: Object, canCreate: Boolean });

const form = useForm({ client_id: "", name: "", start_date: "", end_date: "", attendance_cutoff_date: "", salary_processing_date: "" });

// Pick a month and fill in the usual dates; every field can still be changed.
const suggest = (e) => {
    const value = e.target.value; // yyyy-mm
    if (!value) return;
    const [y, m] = value.split("-").map(Number);
    const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
    const start = new Date(y, m - 1, 1);
    const end = new Date(y, m, 0);
    form.name = start.toLocaleString("en-IN", { month: "long", year: "numeric" });
    form.start_date = iso(start);
    form.end_date = iso(end);
    form.attendance_cutoff_date = iso(new Date(y, m, 2));
    form.salary_processing_date = iso(new Date(y, m, 10));
};

const submit = () => form.post(route("workforce.payroll.periods.store"), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Payroll periods" />

    <AppLayout>
        <PageHeader title="Payroll periods" subtitle="The cycle, attendance cut-off and salary processing date for each client.">
            <Link :href="route('workforce.payroll.index')" class="text-sm text-indigo-600 hover:underline">← Payroll runs</Link>
        </PageHeader>

        <div v-if="canCreate" class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <form class="grid gap-4 md:grid-cols-6" @submit.prevent="submit">
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Client</label>
                    <select v-model="form.client_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="" disabled>Choose…</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                </div>
                <div><label class="text-sm font-medium text-gray-700">Month (quick fill)</label><input type="month" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="suggest" /></div>
                <div class="md:col-span-3"><label class="text-sm font-medium text-gray-700">Name</label><input v-model="form.name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="text-sm font-medium text-gray-700">Starts</label><input v-model="form.start_date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Ends</label><input v-model="form.end_date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Attendance cut-off</label><input v-model="form.attendance_cutoff_date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="text-sm font-medium text-gray-700">Salary date</label><input v-model="form.salary_processing_date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div class="flex items-end md:col-span-2"><button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60">Create period</button></div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Period</th><th class="px-4 py-3">Dates</th><th class="px-4 py-3">Attendance cut-off</th><th class="px-4 py-3">Salary date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Runs</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in periods.data" :key="p.id">
                        <td class="px-4 py-2.5 text-gray-600">{{ p.client }}</td>
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ p.name }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.range }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.cutoff }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ p.processing }}</td>
                        <td class="px-4 py-2.5"><Pill :status="p.status" /></td>
                        <td class="px-4 py-2.5">{{ p.runs }}</td>
                    </tr>
                    <tr v-if="!periods.data.length"><td colspan="7" class="px-4 py-10 text-center text-gray-500">No periods yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="periods.links" />
    </AppLayout>
</template>
