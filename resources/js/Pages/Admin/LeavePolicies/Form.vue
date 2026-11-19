<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ policy: Object, clients: Array, leaveTypes: Array, employmentTypes: Array });

const p = props.policy;
const form = useForm({
    client_id: p?.client_id ?? "",
    leave_type_id: p?.leave_type_id ?? "",
    employment_type: p?.employment_type ?? "",
    effective_from: p?.effective_from ?? new Date().toISOString().slice(0, 4) + "-01-01",
    effective_to: p?.effective_to ?? "",
    is_active: p?.is_active ?? true,
    accrual_frequency: p?.accrual_frequency ?? "monthly",
    accrual_amount: p?.accrual_amount ?? 1,
    annual_cap: p?.annual_cap ?? "",
    credit_day: p?.credit_day ?? 1,
    eligible_after_days: p?.eligible_after_days ?? 0,
    probation_days: p?.probation_days ?? 0,
    allow_during_probation: p?.allow_during_probation ?? true,
    allow_half_day: p?.allow_half_day ?? true,
    max_consecutive_days: p?.max_consecutive_days ?? "",
    min_notice_days: p?.min_notice_days ?? 0,
    allow_negative: p?.allow_negative ?? false,
    max_negative_days: p?.max_negative_days ?? 0,
    carry_forward_mode: p?.carry_forward_mode ?? "none",
    carry_forward_cap: p?.carry_forward_cap ?? "",
    expiry_months: p?.expiry_months ?? "",
    allow_encashment: p?.allow_encashment ?? false,
    count_weekly_offs: p?.count_weekly_offs ?? false,
    count_holidays: p?.count_holidays ?? false,
    approval_levels: p?.approval_levels ?? ["reporting_manager"],
});

const typeOptions = computed(() => props.leaveTypes.filter((t) => !t.client_id || !form.client_id || t.client_id === Number(form.client_id)));
const levels = [["reporting_manager", "Reporting manager"], ["hr", "HR"]];
const toggleLevel = (l) => {
    const next = form.approval_levels.includes(l) ? form.approval_levels.filter((x) => x !== l) : [...form.approval_levels, l];
    form.approval_levels = levels.map((x) => x[0]).filter((x) => next.includes(x));
};

const save = () => {
    const data = form.transform((d) => ({
        ...d,
        client_id: d.client_id || null,
        employment_type: d.employment_type || null,
        annual_cap: d.annual_cap === "" ? null : d.annual_cap,
        max_consecutive_days: d.max_consecutive_days === "" ? null : d.max_consecutive_days,
        carry_forward_cap: d.carry_forward_cap === "" ? null : d.carry_forward_cap,
        expiry_months: d.expiry_months === "" ? null : d.expiry_months,
        effective_to: d.effective_to || null,
    }));
    p ? data.put(route("admin.leave-policies.update", p.id)) : data.post(route("admin.leave-policies.store"));
};
</script>

<template>
    <Head :title="p ? 'Edit leave policy' : 'New leave policy'" />

    <AppLayout>
        <PageHeader :title="p ? 'Edit leave policy' : 'New leave policy'" subtitle="Rules for one leave type. Leave the client empty for a company-wide rule; a client-specific rule always wins.">
            <Link :href="route('admin.leave-policies.index')" class="text-sm text-indigo-600 hover:underline">← Policies</Link>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="save">
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Who and when</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Client</label><select v-model="form.client_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                        <div><label class="text-sm font-medium text-gray-700">Leave type</label><select v-model="form.leave_type_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="" disabled>Choose…</option><option v-for="t in typeOptions" :key="t.id" :value="t.id">{{ t.name }}</option></select></div>
                        <div><label class="text-sm font-medium text-gray-700">Employment type</label><select v-model="form.employment_type" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">Everyone</option><option v-for="t in employmentTypes" :key="t" :value="t" class="capitalize">{{ t.replace("_", " ") }}</option></select></div>
                        <div class="flex items-end"><label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" /> Active</label></div>
                        <div><label class="text-sm font-medium text-gray-700">Effective from</label><input v-model="form.effective_from" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Effective to</label><input v-model="form.effective_to" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <p v-if="form.errors.effective_from" class="text-xs text-red-600">{{ form.errors.effective_from }}</p>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Accrual</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Frequency</label><select v-model="form.accrual_frequency" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="monthly">Monthly</option><option value="yearly">Yearly (1 January)</option><option value="upfront">Upfront (at joining / new year)</option><option value="none">No accrual (e.g. unpaid leave)</option></select></div>
                        <div v-if="form.accrual_frequency !== 'none'"><label class="text-sm font-medium text-gray-700">Days credited each time</label><input v-model.number="form.accrual_amount" type="number" step="0.25" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div v-if="form.accrual_frequency !== 'none'"><label class="text-sm font-medium text-gray-700">Annual cap (days)</label><input v-model="form.annual_cap" type="number" step="0.5" min="0" placeholder="none" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div v-if="form.accrual_frequency === 'monthly'"><label class="text-sm font-medium text-gray-700">Credit on day of month</label><input v-model.number="form.credit_day" type="number" min="1" max="28" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Eligible after (days of service)</label><input v-model.number="form.eligible_after_days" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Probation length (days)</label><input v-model.number="form.probation_days" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div class="flex items-end"><label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.allow_during_probation" type="checkbox" class="rounded border-gray-300" /> Usable during probation</label></div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Applying</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Notice needed (days)</label><input v-model.number="form.min_notice_days" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Max consecutive days</label><input v-model="form.max_consecutive_days" type="number" step="0.5" min="0.5" placeholder="no limit" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.allow_half_day" type="checkbox" class="rounded border-gray-300" /> Half days allowed</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.count_weekly_offs" type="checkbox" class="rounded border-gray-300" /> Weekly offs inside a request count as leave</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.count_holidays" type="checkbox" class="rounded border-gray-300" /> Holidays inside a request count as leave</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.allow_negative" type="checkbox" class="rounded border-gray-300" /> Allow negative balance</label>
                        <div v-if="form.allow_negative"><label class="text-xs text-gray-500">Up to (days)</label><input v-model.number="form.max_negative_days" type="number" step="0.5" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Approval & year end</h2>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Approval chain</p>
                        <div class="mt-2 flex gap-2"><button v-for="l in levels" :key="l[0]" type="button" :class="['rounded-lg px-3 py-1.5 text-sm font-medium', form.approval_levels.includes(l[0]) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600']" @click="toggleLevel(l[0])">{{ l[1] }}</button></div>
                        <p class="mt-1 text-xs text-gray-400">Selected levels must approve in this order.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Unused balance at year end</label><select v-model="form.carry_forward_mode" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="none">Lapses</option><option value="capped">Carry forward up to a cap</option><option value="all">Carry forward all</option></select></div>
                        <div v-if="form.carry_forward_mode === 'capped'"><label class="text-sm font-medium text-gray-700">Carry-forward cap (days)</label><input v-model="form.carry_forward_cap" type="number" step="0.5" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.allow_encashment" type="checkbox" class="rounded border-gray-300" /> Encashment allowed (recorded only; paid through payroll adjustments)</label>
                </section>
            </div>

            <p v-if="Object.keys(form.errors).length" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ Object.values(form.errors).join(" ") }}</p>

            <div class="flex justify-end gap-3">
                <Link :href="route('admin.leave-policies.index')" class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-600">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white disabled:opacity-60">Save policy</button>
            </div>
        </form>
    </AppLayout>
</template>
