<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ groups: Object, reports: Object });
</script>

<template>
    <Head title="Reports" />

    <AppLayout>
        <PageHeader title="Reports" subtitle="View on screen or download as CSV. You only see the employees you are responsible for." />

        <div v-for="(label, key) in groups" :key="key" class="mb-8">
            <template v-if="reports[key]?.length">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ label }}</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="r in reports[key]" :key="r.key" :href="route('workforce.reports.show', r.key)" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:ring-indigo-300">
                        <p class="font-semibold text-gray-900">{{ r.title }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ r.description }}</p>
                    </Link>
                </div>
            </template>
        </div>

        <p v-if="!Object.keys(reports).length" class="rounded-2xl bg-white p-10 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">No reports are available for your role.</p>
    </AppLayout>
</template>
