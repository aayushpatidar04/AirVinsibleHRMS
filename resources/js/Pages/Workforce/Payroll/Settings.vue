<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ clients: Array, clientId: Number, settings: Object, stored: Boolean, history: Array, canEdit: Boolean });

const s = props.settings ?? {};
const form = useForm({
    client_id: props.clientId,
    effective_from: new Date().toISOString().slice(0, 10).slice(0, 8) + "01",
    calculation_method: s.calculation_method ?? "monthly_fixed",
    divisor_mode: s.divisor_mode ?? "calendar_days",
    fixed_divisor: s.fixed_divisor ?? 30,
    rounding: s.rounding ?? "nearest",
    precision: s.precision ?? 0,
    overtime_base: s.overtime_base ?? "gross",
    block_on_pending_attendance: s.block_on_pending_attendance ?? true,
    block_on_pending_leave: s.block_on_pending_leave ?? true,
    separate_reviewer: s.separate_reviewer ?? false,
    pf_enabled: s.pf_enabled ?? true,
    pf_employee_rate: s.pf_employee_rate ?? 12,
    pf_employer_rate: s.pf_employer_rate ?? 12,
    pf_wage_ceiling: s.pf_wage_ceiling ?? "",
    esic_enabled: s.esic_enabled ?? true,
    esic_employee_rate: s.esic_employee_rate ?? 0.75,
    esic_employer_rate: s.esic_employer_rate ?? 3.25,
    esic_gross_ceiling: s.esic_gross_ceiling ?? 21000,
});

const pick = (e) => router.get(route("workforce.payroll.settings"), { client_id: e.target.value });
const save = () => form.transform((d) => ({ ...d, pf_wage_ceiling: d.pf_wage_ceiling === "" ? null : d.pf_wage_ceiling })).post(route("workforce.payroll.settings.store"), { preserveScroll: true });
</script>

<template>
    <Head title="Payroll settings" />

    <AppLayout>
        <PageHeader title="Payroll settings" subtitle="How each client's pay is calculated. A change applies from its effective date; runs already approved keep the settings they used.">
            <Link :href="route('workforce.payroll.index')" class="text-sm text-indigo-600 hover:underline">← Payroll runs</Link>
        </PageHeader>

        <div class="mb-6 max-w-sm">
            <label class="text-sm font-medium text-gray-700">Client</label>
            <select :value="clientId" class="mt-1 w-full rounded-lg border-gray-300 text-sm" @change="pick"><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <p v-if="!stored" class="mt-2 text-xs text-amber-700">No settings are saved for this client yet; the defaults below are being used. Save to make them explicit.</p>
        </div>

        <form class="space-y-6" @submit.prevent="save">
            <fieldset :disabled="!canEdit" class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Calculation</h2>
                    <div><label class="text-sm font-medium text-gray-700">Effective from</label><input v-model="form.effective_from" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="text-sm font-medium text-gray-700">Method</label>
                        <select v-model="form.calculation_method" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="monthly_fixed">Fixed monthly salary (loss of pay for absences)</option><option value="hourly">Hourly (paid for hours worked)</option></select></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Per-day divisor</label>
                            <select v-model="form.divisor_mode" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="calendar_days">Calendar days in the month</option><option value="fixed">Fixed number</option></select></div>
                        <div v-if="form.divisor_mode === 'fixed'"><label class="text-sm font-medium text-gray-700">Divisor</label><input v-model="form.fixed_divisor" type="number" min="20" max="31" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Rounding</label><select v-model="form.rounding" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="nearest">Nearest</option><option value="up">Up</option><option value="down">Down</option></select></div>
                        <div><label class="text-sm font-medium text-gray-700">Decimals</label><select v-model="form.precision" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option :value="0">Whole rupees</option><option :value="2">Paise (2 decimals)</option></select></div>
                    </div>
                    <div><label class="text-sm font-medium text-gray-700">Overtime hourly rate based on</label><select v-model="form.overtime_base" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="gross">Gross salary</option><option value="basic">Basic salary</option></select></div>
                </div>

                <div class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Controls</h2>
                    <label class="flex items-start gap-2 text-sm text-gray-700"><input v-model="form.block_on_pending_attendance" type="checkbox" class="mt-0.5 rounded border-gray-300" /><span>Block approval while attendance exceptions or correction requests are pending</span></label>
                    <label class="flex items-start gap-2 text-sm text-gray-700"><input v-model="form.block_on_pending_leave" type="checkbox" class="mt-0.5 rounded border-gray-300" /><span>Block approval while leave requests in the period are pending</span></label>
                    <label class="flex items-start gap-2 text-sm text-gray-700"><input v-model="form.separate_reviewer" type="checkbox" class="mt-0.5 rounded border-gray-300" /><span>Separation of duties: the person who prepared a run cannot review or approve it</span></label>

                    <h2 class="pt-2 text-sm font-semibold text-gray-800">Provident fund (PF)</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.pf_enabled" type="checkbox" class="rounded border-gray-300" /> PF applies</label>
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="text-xs text-gray-500">Employee %</label><input v-model="form.pf_employee_rate" type="number" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-xs text-gray-500">Employer %</label><input v-model="form.pf_employer_rate" type="number" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-xs text-gray-500">Wage ceiling ₹</label><input v-model="form.pf_wage_ceiling" type="number" placeholder="none" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>

                    <h2 class="pt-2 text-sm font-semibold text-gray-800">ESIC</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.esic_enabled" type="checkbox" class="rounded border-gray-300" /> ESIC applies</label>
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="text-xs text-gray-500">Employee %</label><input v-model="form.esic_employee_rate" type="number" step="0.001" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-xs text-gray-500">Employer %</label><input v-model="form.esic_employer_rate" type="number" step="0.001" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-xs text-gray-500">Gross ceiling ₹</label><input v-model="form.esic_gross_ceiling" type="number" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Statutory rates and ceilings change by law. Have payroll or your CA confirm these figures before the first live run.</p>
                </div>
            </fieldset>

            <div v-if="canEdit" class="flex justify-end"><button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white disabled:opacity-60">Save settings</button></div>
            <p v-if="Object.keys(form.errors).length" class="text-sm text-red-600">Please check the highlighted values: {{ Object.values(form.errors).join(" ") }}</p>
        </form>

        <div v-if="history.length" class="mt-8">
            <h2 class="mb-2 text-sm font-semibold text-gray-800">Saved versions</h2>
            <ul class="rounded-2xl bg-white p-4 text-xs text-gray-600 shadow-sm ring-1 ring-gray-100">
                <li v-for="h in history" :key="h.id" class="py-0.5">From <b>{{ h.effective_from }}</b> · {{ h.method.replace("_", " ") }} · divisor {{ h.divisor }}</li>
            </ul>
        </div>
    </AppLayout>
</template>
