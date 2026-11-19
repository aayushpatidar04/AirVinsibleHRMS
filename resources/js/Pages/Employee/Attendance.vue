<script setup>
import { computed, reactive, ref } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import MonthNav from "@/Components/Workforce/MonthNav.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { flagLabels, formatMinutes, punchLabels } from "@/Utils/workforce";

const props = defineProps({
    month: String,
    monthLabel: String,
    prevMonth: String,
    nextMonth: String,
    days: Array,
    summary: Object,
    corrections: Array,
});

const open = ref(null);
const showForm = ref(false);

const form = useForm({ date: "", in_time: "", out_time: "", reason: "" });

const requestFor = (day) => {
    form.reset();
    form.date = day?.date ?? "";
    showForm.value = true;
};

const submit = () =>
    form.post(route("employee.attendance.corrections.store"), {
        preserveScroll: true,
        onSuccess: () => (showForm.value = false),
    });

const cancel = (c) => router.post(route("employee.attendance.corrections.cancel", c.id), {}, { preserveScroll: true });

const cards = computed(() => [
    ["Present", props.summary.present, "text-emerald-600"],
    ["Half day", props.summary.half_day, "text-amber-600"],
    ["Absent", props.summary.absent, "text-red-600"],
    ["Leave", props.summary.leave, "text-sky-600"],
    ["Late", props.summary.late, "text-orange-600"],
    ["To fix", props.summary.exceptions, "text-orange-600"],
    ["Worked", formatMinutes(props.summary.worked_minutes), "text-gray-800"],
]);
</script>

<template>
    <Head title="My attendance" />

    <AppLayout>
        <PageHeader title="My attendance" subtitle="Your daily record. If a punch is missing or wrong, request a correction and your manager will approve it.">
            <MonthNav :label="monthLabel" :prev="prevMonth" :next="nextMonth" route-name="employee.attendance" />
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @click="requestFor(null)">Request correction</button>
        </PageHeader>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div v-for="c in cards" :key="c[0]" class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100">
                <p :class="['text-2xl font-bold', c[2]]">{{ c[1] }}</p>
                <p class="text-xs text-gray-500">{{ c[0] }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">In</th><th class="px-4 py-3">Out</th>
                        <th class="px-4 py-3">Worked</th><th class="px-4 py-3">Notes</th><th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template v-for="d in days" :key="d.date">
                        <tr :class="['cursor-pointer hover:bg-gray-50', d.is_today ? 'bg-indigo-50/40' : '']" @click="open = open === d.date ? null : d.date">
                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ d.day }} <span class="text-gray-400">{{ d.weekday }}</span></td>
                            <td class="px-4 py-2.5"><Pill :status="d.status" /></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ d.first_in ?? "—" }}<span v-if="d.late_minutes" class="ml-1 text-xs text-red-600">+{{ d.late_minutes }}m</span></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ d.last_out ?? "—" }}<span v-if="d.early_minutes" class="ml-1 text-xs text-red-600">−{{ d.early_minutes }}m</span></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ formatMinutes(d.worked_minutes) }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500">
                                <span v-if="d.note">{{ d.note }}</span>
                                <span v-for="f in d.flags" :key="f" class="mr-1 rounded bg-orange-50 px-1.5 py-0.5 text-orange-700">{{ flagLabels[f] ?? f }}</span>
                                <span v-if="d.locked" class="text-gray-400">🔒 payroll</span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <button v-if="d.can_correct && (d.status === 'incomplete' || d.status === 'absent' || d.flags.length)" class="text-xs font-medium text-indigo-600 hover:underline" @click.stop="requestFor(d)">Correct</button>
                            </td>
                        </tr>
                        <tr v-if="open === d.date" class="bg-gray-50">
                            <td colspan="7" class="px-6 py-3 text-xs text-gray-600">
                                <p v-if="!d.punches.length" class="text-gray-400">No punches on this day.</p>
                                <ul v-else class="flex flex-wrap gap-x-6 gap-y-1">
                                    <li v-for="(p, i) in d.punches" :key="i">{{ punchLabels[p.type] }} · <b>{{ p.time }}</b> <span class="uppercase text-gray-400">{{ p.source }}</span></li>
                                </ul>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold text-gray-800">My correction requests</h2>
            <div v-if="corrections.length" class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="c in corrections" :key="c.id">
                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ c.date }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ c.requested_in ?? "—" }} → {{ c.requested_out ?? "—" }}</td>
                            <td class="px-4 py-2.5 text-gray-500">{{ c.reason }}<span v-if="c.decision_note" class="block text-xs text-gray-400">Reply: {{ c.decision_note }}</span></td>
                            <td class="px-4 py-2.5"><Pill :status="c.status" /></td>
                            <td class="px-4 py-2.5 text-right"><button v-if="c.status === 'pending'" class="text-xs text-red-600 hover:underline" @click="cancel(c)">Cancel</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-gray-500">No requests yet.</p>
        </div>

        <Modal :show="showForm" title="Request attendance correction" subtitle="Tell us the correct times. Your manager will review it." @close="showForm = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700">Date</label>
                    <input v-model="form.date" type="date" :max="new Date().toISOString().slice(0, 10)" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Punch-in time</label>
                        <input v-model="form.in_time" type="time" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Punch-out time</label>
                        <input v-model="form.out_time" type="time" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                </div>
                <p class="text-xs text-gray-500">Fill only what is wrong or missing. An exit earlier than the entry is treated as the next morning (night shift).</p>
                <div>
                    <label class="text-sm font-medium text-gray-700">Reason</label>
                    <textarea v-model="form.reason" rows="3" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600" @click="showForm = false">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Send request</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
