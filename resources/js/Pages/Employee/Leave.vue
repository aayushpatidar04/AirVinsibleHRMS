<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ balances: Array, applications: Array });

const form = useForm({
    leave_type_id: props.balances[0]?.leave_type_id ?? "",
    start_date: "",
    end_date: "",
    is_half_day: false,
    half_day_session: "first_half",
    reason: "",
});

const selected = computed(() => props.balances.find((b) => b.leave_type_id === form.leave_type_id));
const preview = ref(null);
const checking = ref(false);
let timer;

// Ask the server whether this request would be accepted, as the form is filled in.
watch(
    () => [form.leave_type_id, form.start_date, form.end_date, form.is_half_day],
    () => {
        preview.value = null;
        clearTimeout(timer);

        if (!form.leave_type_id || !form.start_date || !form.end_date) return;

        timer = setTimeout(async () => {
            checking.value = true;
            try {
                const { data } = await axios.post(route("employee.leave.preview"), {
                    leave_type_id: form.leave_type_id,
                    start_date: form.start_date,
                    end_date: form.end_date,
                    is_half_day: form.is_half_day,
                });
                preview.value = data;
            } catch {
                preview.value = { ok: false, message: "Could not check this request. Please try again." };
            } finally {
                checking.value = false;
            }
        }, 350);
    },
);

watch(() => form.is_half_day, (v) => v && (form.end_date = form.start_date));
watch(() => form.start_date, (v) => (form.is_half_day || !form.end_date || form.end_date < v) && (form.end_date = v));

const submit = () =>
    form.post(route("employee.leave.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset("start_date", "end_date", "reason", "is_half_day"),
    });

const cancel = (a) => confirm("Cancel this leave request?") && router.post(route("employee.leave.cancel", a.id), {}, { preserveScroll: true });
const levelText = (levels) => levels.map((l) => l.replace("_", " ")).join(" → ");
</script>

<template>
    <Head title="My leave" />

    <AppLayout>
        <PageHeader title="My leave" subtitle="Your balances, requests and their approval status." />

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div v-for="b in balances" :key="b.leave_type_id" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <p class="text-xs text-gray-500">{{ b.name }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ b.is_paid ? b.available : "No limit" }}</p>
                <p v-if="b.is_paid" class="text-xs text-gray-400">Balance {{ b.balance }}<span v-if="b.pending"> · {{ b.pending }} pending</span></p>
                <p v-else class="text-xs text-gray-400">Unpaid</p>
            </div>
            <p v-if="!balances.length" class="col-span-full rounded-xl bg-amber-50 p-4 text-sm text-amber-800">No leave policy applies to you yet. Please ask HR.</p>
        </div>

        <div v-if="balances.length" class="mb-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-sm font-semibold text-gray-800">Apply for leave</h2>
            <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700">Leave type</label>
                    <select v-model="form.leave_type_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        <option v-for="b in balances" :key="b.leave_type_id" :value="b.leave_type_id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">From</label>
                    <input v-model="form.start_date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">To</label>
                    <input v-model="form.end_date" type="date" required :min="form.start_date" :disabled="form.is_half_day" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div class="flex flex-col justify-end">
                    <label v-if="selected?.allow_half_day" class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="form.is_half_day" type="checkbox" class="rounded border-gray-300" /> Half day
                    </label>
                    <select v-if="form.is_half_day" v-model="form.half_day_session" class="mt-2 w-full rounded-lg border-gray-300 text-sm">
                        <option value="first_half">First half</option>
                        <option value="second_half">Second half</option>
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label class="text-sm font-medium text-gray-700">Reason</label>
                    <textarea v-model="form.reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                </div>

                <div class="md:col-span-4">
                    <p v-if="checking" class="text-sm text-gray-400">Checking…</p>
                    <p v-else-if="preview?.ok" class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                        This will use <b>{{ preview.days }}</b> day(s). Approval: {{ levelText(preview.levels) }}.
                    </p>
                    <p v-else-if="preview && !preview.ok" class="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{{ preview.message }}</p>
                </div>

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" :disabled="form.processing || (preview && !preview.ok)" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        Submit request
                    </button>
                </div>
            </form>
        </div>

        <h2 class="mb-3 text-sm font-semibold text-gray-800">My requests</h2>
        <div v-if="applications.length" class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Type</th><th class="px-4 py-3">Dates</th><th class="px-4 py-3">Days</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Approval</th><th class="px-4 py-3" /></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="a in applications" :key="a.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ a.type }}<span v-if="a.half_day" class="block text-xs font-normal text-gray-400">{{ a.half_day }}</span></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ a.start_date }}<span v-if="a.start_date !== a.end_date"> – {{ a.end_date }}</span></td>
                        <td class="px-4 py-2.5 text-gray-600">{{ a.days }}</td>
                        <td class="px-4 py-2.5"><Pill :status="a.status" /></td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">
                            <div v-for="(ap, i) in a.approvals" :key="i" class="capitalize">
                                {{ ap.role }}: {{ ap.status }}<span v-if="ap.by"> ({{ ap.by }})</span><span v-if="ap.note" class="block normal-case text-gray-400">"{{ ap.note }}"</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-right"><button v-if="a.can_cancel" class="text-xs text-red-600 hover:underline" @click="cancel(a)">Cancel</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-gray-500">You have not applied for leave yet.</p>
    </AppLayout>
</template>
