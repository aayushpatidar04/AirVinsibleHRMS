<script setup>
import { computed } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { formatMoney } from "@/Utils/workforce";

const props = defineProps({ employee: Object, current: Object, history: Array, offer: Object, rates: Object, canEdit: Boolean });

const blank = () => ({ name: "", type: "earning", calc_type: "fixed", amount: 0, percentage: null, prorate: true, is_basic: false, statutory_code: null });

const nextDay = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
};

const form = useForm({
    effective_from: props.current ? nextDay() : props.employee.joined ?? new Date().toISOString().slice(0, 10),
    hourly_rate: props.current?.hourly_rate ?? "",
    notes: "",
    items: props.current ? props.current.items.map((i) => ({ ...i })) : [{ ...blank(), name: "Basic", is_basic: true }, { ...blank(), name: "HRA" }],
});

const add = (preset = {}) => form.items.push({ ...blank(), ...preset });
const remove = (i) => form.items.splice(i, 1);

// Statutory rows pre-filled from the client's payroll settings.
const addStatutory = () => {
    const r = props.rates ?? {};
    [
        { name: "PF employee Contribution", type: "deduction", calc_type: "percent_of_basic", percentage: r.pf_employee_rate ?? 12, statutory_code: "pf_employee" },
        { name: "ESIC employee Contribution", type: "deduction", calc_type: "percent_of_basic", percentage: r.esic_employee_rate ?? 0.75, statutory_code: "esic_employee" },
        { name: "PF Company Contribution", type: "employer_contribution", calc_type: "percent_of_basic", percentage: r.pf_employer_rate ?? 12, statutory_code: "pf_employer" },
        { name: "ESIC Company Contribution", type: "employer_contribution", calc_type: "percent_of_basic", percentage: r.esic_employer_rate ?? 3.25, statutory_code: "esic_employer" },
    ].forEach((p) => add(p));
};

const monthly = (type) => form.items.filter((i) => i.type === type && i.calc_type === "fixed").reduce((s, i) => s + (Number(i.amount) || 0), 0);
const gross = computed(() => monthly("earning"));

const save = () => form.post(route("workforce.payroll.structures.store", props.employee.id), { preserveScroll: true });
const importOffer = () => confirm("Create the salary structure from the accepted offer?") && router.post(route("workforce.payroll.structures.import-offer", props.employee.id), {}, { preserveScroll: true });

const typeLabel = { earning: "Earning", deduction: "Deduction", employer_contribution: "Employer contribution" };
</script>

<template>
    <Head :title="`${employee.name} · salary structure`" />

    <AppLayout>
        <PageHeader :title="employee.name" :subtitle="`${employee.employee_id} · ${employee.client ?? 'No client'} · salary structure`">
            <Link :href="route('workforce.payroll.structures')" class="text-sm text-indigo-600 hover:underline">← All structures</Link>
        </PageHeader>

        <div v-if="offer && canEdit" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
            <p class="text-sm text-indigo-900">An accepted offer exists ({{ offer.number }} · {{ offer.designation }}<span v-if="offer.joining_date"> · joining {{ offer.joining_date }}</span>). You can build the structure from it.</p>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @click="importOffer">Import from offer</button>
        </div>
        <p v-if="form.errors.import" class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{{ form.errors.import }}</p>

        <form v-if="canEdit" class="mb-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="save">
            <h2 class="mb-1 text-sm font-semibold text-gray-800">{{ current ? "Revise structure" : "New structure" }}</h2>
            <p class="mb-4 text-xs text-gray-500">Saving creates a new version effective from the date below and closes the previous one the day before.</p>

            <div class="mb-4 grid gap-4 md:grid-cols-3">
                <div><label class="text-sm font-medium text-gray-700">Effective from</label><input v-model="form.effective_from" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p v-if="form.errors.effective_from" class="mt-1 text-xs text-red-600">{{ form.errors.effective_from }}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Hourly rate (hourly clients only)</label><input v-model="form.hourly_rate" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="text-sm font-medium text-gray-700">Note</label><input v-model="form.notes" maxlength="255" placeholder="e.g. Annual revision" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-2 py-2">Component</th><th class="px-2 py-2">Type</th><th class="px-2 py-2">Calculated as</th><th class="px-2 py-2">Monthly ₹ / %</th><th class="px-2 py-2">Basic</th><th class="px-2 py-2">Pro-rate</th><th class="px-2 py-2">Statutory</th><th /></tr></thead>
                    <tbody>
                        <tr v-for="(item, i) in form.items" :key="i" class="border-t border-gray-100">
                            <td class="px-2 py-1.5"><input v-model="item.name" required class="w-44 rounded-lg border-gray-300 text-sm" /></td>
                            <td class="px-2 py-1.5"><select v-model="item.type" class="rounded-lg border-gray-300 text-sm"><option v-for="(l, k) in typeLabel" :key="k" :value="k">{{ l }}</option></select></td>
                            <td class="px-2 py-1.5"><select v-model="item.calc_type" class="rounded-lg border-gray-300 text-sm"><option value="fixed">Fixed amount</option><option value="percent_of_basic">% of basic</option><option value="percent_of_gross">% of gross</option></select></td>
                            <td class="px-2 py-1.5">
                                <input v-if="item.calc_type === 'fixed'" v-model="item.amount" type="number" step="0.01" min="0" class="w-28 rounded-lg border-gray-300 text-sm" />
                                <input v-else v-model="item.percentage" type="number" step="0.0001" min="0" max="100" class="w-28 rounded-lg border-gray-300 text-sm" />
                            </td>
                            <td class="px-2 py-1.5 text-center"><input v-model="item.is_basic" type="checkbox" :disabled="item.type !== 'earning'" class="rounded border-gray-300" /></td>
                            <td class="px-2 py-1.5 text-center"><input v-model="item.prorate" type="checkbox" class="rounded border-gray-300" /></td>
                            <td class="px-2 py-1.5">
                                <select v-model="item.statutory_code" class="rounded-lg border-gray-300 text-xs"><option :value="null">—</option><option value="pf_employee">PF employee</option><option value="pf_employer">PF employer</option><option value="esic_employee">ESIC employee</option><option value="esic_employer">ESIC employer</option></select>
                            </td>
                            <td class="px-2 py-1.5 text-right"><button type="button" class="text-xs text-red-600 hover:underline" @click="remove(i)">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="add()">+ Add component</button>
                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="addStatutory">+ Add PF / ESIC from client settings</button>
                <span class="ml-auto text-gray-600">Fixed monthly gross: <b>₹ {{ formatMoney(gross) }}</b></span>
            </div>
            <p v-if="form.errors.items" class="mt-3 text-sm text-red-600">{{ form.errors.items }}</p>

            <div class="mt-5 flex justify-end"><button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white disabled:opacity-60">Save new version</button></div>
        </form>

        <h2 class="mb-2 text-sm font-semibold text-gray-800">Version history</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="h in history" :key="h.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ h.effective_from }} → {{ h.effective_to ?? "now" }}</td>
                        <td class="px-4 py-2.5">₹ {{ formatMoney(h.gross) }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ h.from_offer ? "From offer · " : "" }}{{ h.notes }}</td>
                    </tr>
                    <tr v-if="!history.length"><td class="px-4 py-6 text-center text-gray-500">No structure yet.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
