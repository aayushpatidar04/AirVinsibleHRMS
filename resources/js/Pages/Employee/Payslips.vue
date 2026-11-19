<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

defineProps({ payslips: Array });
</script>

<template>
    <Head title="My payslips" />

    <AppLayout>
        <PageHeader title="My payslips" subtitle="Payslips appear here once payroll for the month has been approved and published." />

        <div v-if="payslips.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="p in payslips" :key="p.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ p.period }}</p>
                        <p class="text-xs text-gray-400">{{ p.number }} · published {{ p.published_at }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Gross</dt><dd>₹ {{ formatMoney(p.gross) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Deductions</dt><dd>₹ {{ formatMoney(p.deductions) }}</dd></div>
                    <div class="flex justify-between border-t pt-1 font-semibold"><dt>Net pay</dt><dd>₹ {{ formatMoney(p.net) }}</dd></div>
                    <div v-if="p.paid_days !== null" class="flex justify-between text-xs text-gray-400"><dt>Paid days</dt><dd>{{ p.paid_days }}</dd></div>
                </dl>
                <a :href="route('employee.payslips.download', p.id)" class="mt-4 block rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-700">Download PDF</a>
            </div>
        </div>
        <div v-else class="rounded-2xl bg-white p-10 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">No payslips have been published for you yet.</div>
    </AppLayout>
</template>
