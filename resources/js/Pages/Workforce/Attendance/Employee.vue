<script setup>
import { ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import MonthNav from "@/Components/Workforce/MonthNav.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";
import { flagLabels, formatMinutes, punchLabels } from "@/Utils/workforce";

const props = defineProps({ employee: Object, month: String, monthLabel: String, prevMonth: String, nextMonth: String, days: Array, statuses: Array, canEdit: Boolean });

const open = ref(null);
const showPunch = ref(false);
const showOverride = ref(null);

const punch = useForm({ type: "in", date: "", time: "", reason: "" });
const override = useForm({ status: "", reason: "" });

const startPunch = (d) => { punch.reset(); punch.date = d.date; showPunch.value = true; };
const submitPunch = () => punch.post(route("workforce.attendance.manual-punch", props.employee.id), { preserveScroll: true, onSuccess: () => (showPunch.value = false) });

const startOverride = (d) => { override.reset(); override.status = d.override ?? ""; showOverride.value = d; };
const submitOverride = () => override.post(route("workforce.attendance.records.override", showOverride.value.record_id), { preserveScroll: true, onSuccess: () => (showOverride.value = null) });

const voidPunch = (p) => {
    const reason = prompt("Why is this punch wrong? (it is voided, not deleted)");
    if (reason) router.post(route("workforce.attendance.punches.void", p.id), { reason }, { preserveScroll: true });
};
const toggleOt = (d) => router.post(route("workforce.attendance.records.overtime", d.record_id), { approve: !d.overtime_approved }, { preserveScroll: true });
const recalc = () => router.post(route("workforce.attendance.recalculate", props.employee.id), { month: props.month }, { preserveScroll: true });
const markReviewed = (d) => {
    const note = prompt("Mark this day as reviewed? Add a note (optional):", "");
    if (note !== null) router.post(route("workforce.attendance.records.review", d.record_id), { note }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`${employee.name} · attendance`" />

    <AppLayout>
        <PageHeader :title="employee.name" :subtitle="`${employee.employee_id} · ${employee.client ?? 'No client'} · ${employee.designation ?? ''}`">
            <MonthNav :label="monthLabel" :prev="prevMonth" :next="nextMonth" route-name="workforce.attendance.employee" :params="{ employee: employee.id }" />
            <button v-if="canEdit" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="recalc">Recalculate month</button>
            <Link :href="route('workforce.attendance.index')" class="text-sm text-indigo-600 hover:underline">← Daily board</Link>
        </PageHeader>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">In</th><th class="px-4 py-3">Out</th><th class="px-4 py-3">Worked</th><th class="px-4 py-3">Break</th><th class="px-4 py-3">OT</th><th class="px-4 py-3">Notes</th><th class="px-4 py-3" /></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template v-for="d in days" :key="d.date">
                        <tr :class="['cursor-pointer hover:bg-gray-50', d.review_status === 'needs_review' ? 'bg-orange-50/50' : '']" @click="open = open === d.date ? null : d.date">
                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ d.label }}</td>
                            <td class="px-4 py-2.5"><Pill :status="d.status" /><span v-if="d.override" class="ml-1 text-xs text-indigo-600" title="Manually set">✎</span></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ d.first_in ?? "—" }}<span v-if="d.late_minutes" class="ml-1 text-xs text-red-600">+{{ d.late_minutes }}m</span></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ d.last_out ?? "—" }}<span v-if="d.early_minutes" class="ml-1 text-xs text-red-600">−{{ d.early_minutes }}m</span></td>
                            <td class="px-4 py-2.5 text-gray-600">{{ formatMinutes(d.worked_minutes) }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ formatMinutes(d.break_minutes) }}</td>
                            <td class="px-4 py-2.5 text-gray-600">
                                <template v-if="d.overtime_minutes">
                                    {{ formatMinutes(d.overtime_minutes) }}
                                    <button v-if="canEdit && !d.locked" class="ml-1 text-xs" :class="d.overtime_approved ? 'text-emerald-700' : 'text-indigo-600'" @click.stop="toggleOt(d)">{{ d.overtime_approved ? "✓ approved" : "approve" }}</button>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs">
                                <span v-if="d.note" class="text-gray-500">{{ d.note }} </span>
                                <span v-for="f in d.flags" :key="f" class="mr-1 rounded bg-orange-50 px-1.5 py-0.5 text-orange-700">{{ flagLabels[f] ?? f }}</span>
                                <span v-if="d.locked" class="text-gray-400">🔒</span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs">
                                <button v-if="canEdit && !d.locked && d.review_status === 'needs_review'" class="font-medium text-emerald-700 hover:underline" @click.stop="markReviewed(d)">Reviewed</button>
                            </td>
                        </tr>
                        <tr v-if="open === d.date" class="bg-gray-50">
                            <td colspan="9" class="px-6 py-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Punches (originals are never edited)</p>
                                        <ul class="space-y-1 text-xs text-gray-700">
                                            <li v-for="p in d.punches" :key="p.id" :class="p.voided ? 'text-gray-400 line-through' : ''">
                                                {{ punchLabels[p.type] }} · <b>{{ p.time }}</b> · <span class="uppercase">{{ p.source }}</span>
                                                <span v-if="p.geo && p.geo !== 'not_required'"> · {{ p.geo }}<span v-if="p.distance !== null"> ({{ p.distance }} m)</span></span>
                                                <span v-if="p.by"> · by {{ p.by }}</span>
                                                <span v-if="p.notes" class="text-gray-400"> — {{ p.notes }}</span>
                                                <span v-if="p.voided" class="no-underline"> (voided: {{ p.void_reason }})</span>
                                                <button v-if="canEdit && !d.locked && !p.voided" class="ml-2 text-red-600 hover:underline" @click="voidPunch(p)">void</button>
                                            </li>
                                            <li v-if="!d.punches.length" class="text-gray-400">No punches.</li>
                                        </ul>
                                        <p v-if="d.session_minutes" class="mt-2 text-xs text-gray-500">System session {{ formatMinutes(d.session_minutes) }}</p>
                                        <p v-if="d.override_reason" class="mt-2 text-xs text-indigo-700">Status set manually: {{ d.override_reason }}</p>
                                    </div>
                                    <div v-if="canEdit && !d.locked" class="flex flex-wrap items-start gap-2">
                                        <button class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100" @click="startPunch(d)">Add manual punch</button>
                                        <button v-if="d.record_id" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100" @click="startOverride(d)">Set status</button>
                                    </div>
                                    <p v-else-if="d.locked" class="text-xs text-gray-400">Locked by an approved payroll run.</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <Modal :show="showPunch" title="Add manual punch" subtitle="Recorded with your name and reason; the day is recalculated." @close="showPunch = false">
            <form class="space-y-4 p-6" @submit.prevent="submitPunch">
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="text-sm font-medium text-gray-700">Type</label>
                        <select v-model="punch.type" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option v-for="(l, k) in punchLabels" :key="k" :value="k">{{ l }}</option></select></div>
                    <div><label class="text-sm font-medium text-gray-700">Date</label><input v-model="punch.date" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="text-sm font-medium text-gray-700">Time</label><input v-model="punch.time" type="time" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                </div>
                <div><label class="text-sm font-medium text-gray-700">Reason</label><textarea v-model="punch.reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <p v-if="punch.errors.punch" class="text-xs text-red-600">{{ punch.errors.punch }}</p>
                <div class="flex justify-end gap-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="showPunch = false">Cancel</button><button type="submit" :disabled="punch.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Add punch</button></div>
            </form>
        </Modal>

        <Modal :show="!!showOverride" title="Set attendance status" subtitle="Overrides the calculated status for this day. Leave empty and save to remove an override." @close="showOverride = null">
            <form class="space-y-4 p-6" @submit.prevent="submitOverride">
                <div><label class="text-sm font-medium text-gray-700">Status</label>
                    <select v-model="override.status" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">Use calculated status</option><option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s.replace("_", " ") }}</option></select></div>
                <div><label class="text-sm font-medium text-gray-700">Reason</label><textarea v-model="override.reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div class="flex justify-end gap-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="showOverride = null">Cancel</button><button type="submit" :disabled="override.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save</button></div>
            </form>
        </Modal>
    </AppLayout>
</template>
