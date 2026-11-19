<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ year: Number, holidays: Array, clients: Array });

const form = useForm({ date: "", name: "", client_id: "" });

const add = () =>
    form.transform((d) => ({ ...d, client_id: d.client_id || null })).post(route("workforce.setup.holidays.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset("date", "name"),
    });

const remove = (h) => confirm(`Remove ${h.name}?`) && router.delete(route("workforce.setup.holidays.destroy", h.id), { preserveScroll: true });
</script>

<template>
    <Head title="Holidays" />

    <AppLayout>
        <PageHeader title="Holidays" subtitle="Company-wide holidays apply to every client; a client holiday applies only to that client's employees.">
            <Link :href="route('workforce.setup.holidays', { year: year - 1 })" class="rounded-lg border bg-white px-3 py-1.5 text-sm">←</Link>
            <span class="min-w-[4rem] text-center text-sm font-semibold">{{ year }}</span>
            <Link :href="route('workforce.setup.holidays', { year: year + 1 })" class="rounded-lg border bg-white px-3 py-1.5 text-sm">→</Link>
        </PageHeader>

        <div class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <form class="flex flex-wrap items-end gap-4" @submit.prevent="add">
                <div>
                    <label class="text-sm font-medium text-gray-700">Date</label>
                    <input v-model="form.date" type="date" required class="mt-1 rounded-lg border-gray-300 text-sm" />
                    <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
                </div>
                <div class="min-w-[14rem] flex-1">
                    <label class="text-sm font-medium text-gray-700">Holiday</label>
                    <input v-model="form.name" required placeholder="Diwali" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Applies to</label>
                    <select v-model="form.client_id" class="mt-1 rounded-lg border-gray-300 text-sm">
                        <option value="">All clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60">Add holiday</button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="h in holidays" :key="h.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ h.label }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ h.name }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ h.client }}</td>
                        <td class="px-4 py-2.5 text-right"><button class="text-xs font-medium text-red-600 hover:underline" @click="remove(h)">Remove</button></td>
                    </tr>
                    <tr v-if="!holidays.length"><td class="px-4 py-10 text-center text-gray-500">No holidays recorded for {{ year }}.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
