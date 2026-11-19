<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

const props = defineProps({ policy: Object, clients: Array, defaults: Object, usedDays: { type: Number, default: 0 } });

const p = props.policy;
const form = useForm({
    client_id: p?.client_id ?? props.defaults?.client_id ?? "",
    name: p?.name ?? "",
    description: p?.description ?? "",
    effective_from: p?.effective_from ?? props.defaults?.effective_from ?? "",
    effective_to: p?.effective_to ?? "",
    is_active: p?.is_active ?? true,
    weekly_offs: p?.weekly_offs ?? [7],
    full_day_minutes: p?.full_day_minutes ?? 480,
    half_day_minutes: p?.half_day_minutes ?? 240,
    min_session_minutes: p?.min_session_minutes ?? 0,
    grace_minutes: p?.grace_minutes ?? 10,
    early_departure_minutes: p?.early_departure_minutes ?? 0,
    max_late_per_month: p?.max_late_per_month ?? 0,
    lates_per_half_day: p?.lates_per_half_day ?? 0,
    deduct_late: p?.deduct_late ?? false,
    deduct_early: p?.deduct_early ?? false,
    require_portal_punch: p?.require_portal_punch ?? true,
    require_machine_punch: p?.require_machine_punch ?? false,
    source_priority: p?.source_priority ?? ["machine", "portal"],
    source_tolerance_minutes: p?.source_tolerance_minutes ?? 15,
    geofence_required: p?.geofence_required ?? true,
    paid_break_minutes: p?.paid_break_minutes ?? 30,
    max_break_minutes: p?.max_break_minutes ?? 60,
    deduct_excess_break: p?.deduct_excess_break ?? true,
    track_idle_time: p?.track_idle_time ?? false,
    overtime_eligible: p?.overtime_eligible ?? false,
    overtime_min_minutes: p?.overtime_min_minutes ?? 30,
    overtime_multiplier: p?.overtime_multiplier ?? 1.5,
    overtime_requires_approval: p?.overtime_requires_approval ?? true,
    missing_punch_treatment: p?.missing_punch_treatment ?? "review",
});

const days = [[1, "Mon"], [2, "Tue"], [3, "Wed"], [4, "Thu"], [5, "Fri"], [6, "Sat"], [7, "Sun"]];
const toggleDay = (d) => (form.weekly_offs = form.weekly_offs.includes(d) ? form.weekly_offs.filter((x) => x !== d) : [...form.weekly_offs, d]);

const sources = [["machine", "Machine / biometric"], ["portal", "Web portal"], ["agent", "Desktop agent"]];
const moveSource = (s, dir) => {
    const list = [...form.source_priority];
    const i = list.indexOf(s);
    const j = i + dir;
    if (i < 0 || j < 0 || j >= list.length) return;
    [list[i], list[j]] = [list[j], list[i]];
    form.source_priority = list;
};
const toggleSource = (s) => (form.source_priority = form.source_priority.includes(s) ? form.source_priority.filter((x) => x !== s) : [...form.source_priority, s]);

const save = () => (p ? form.put(route("admin.attendance-policies.update", p.id)) : form.post(route("admin.attendance-policies.store")));
const hrs = (m) => `${Math.floor(m / 60)}h ${m % 60 ? (m % 60) + "m" : ""}`.trim();
</script>

<template>
    <Head :title="p ? 'Edit attendance policy' : 'New attendance policy'" />

    <AppLayout>
        <PageHeader :title="p ? 'Edit attendance policy' : 'New attendance policy'" subtitle="Applies only to the chosen client's employees, from the effective date.">
            <Link :href="route('admin.attendance-policies.index')" class="text-sm text-indigo-600 hover:underline">← Policies</Link>
        </PageHeader>

        <p v-if="p && usedDays" class="mb-4 rounded-xl bg-sky-50 px-4 py-3 text-xs text-sky-800">This policy has been applied to {{ usedDays }} attendance day(s). Editing it changes how <b>future</b> recalculations behave; days locked by approved payroll keep their frozen copy. For a rule change from a date, end this policy and create a new one.</p>

        <form class="space-y-6" @submit.prevent="save">
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Basics</h2>
                    <div><label class="text-sm font-medium text-gray-700">Client</label>
                        <select v-model="form.client_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="" disabled>Choose…</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                        <p v-if="form.errors.client_id" class="mt-1 text-xs text-red-600">{{ form.errors.client_id }}</p></div>
                    <div><label class="text-sm font-medium text-gray-700">Name</label><input v-model="form.name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Effective from</label><input v-model="form.effective_from" type="date" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p v-if="form.errors.effective_from" class="mt-1 text-xs text-red-600">{{ form.errors.effective_from }}</p></div>
                        <div><label class="text-sm font-medium text-gray-700">Effective to</label><input v-model="form.effective_to" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" /> Active</label>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Weekly offs</p>
                        <div class="mt-2 flex gap-2"><button v-for="d in days" :key="d[0]" type="button" :class="['rounded-lg px-3 py-1.5 text-sm font-medium', form.weekly_offs.includes(d[0]) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600']" @click="toggleDay(d[0])">{{ d[1] }}</button></div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Working hours</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Full day (minutes)</label><input v-model.number="form.full_day_minutes" type="number" min="30" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-gray-400">{{ hrs(form.full_day_minutes) }}</p></div>
                        <div><label class="text-sm font-medium text-gray-700">Half day (minutes)</label><input v-model.number="form.half_day_minutes" type="number" min="15" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-gray-400">{{ hrs(form.half_day_minutes) }}</p><p v-if="form.errors.half_day_minutes" class="text-xs text-red-600">{{ form.errors.half_day_minutes }}</p></div>
                        <div><label class="text-sm font-medium text-gray-700">Late grace (minutes)</label><input v-model.number="form.grace_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Early exit allowed (minutes)</label><input v-model.number="form.early_departure_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div class="col-span-2"><label class="text-sm font-medium text-gray-700">Minimum system session (minutes, 0 = not checked)</label><input v-model.number="form.min_session_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Late / early penalties</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.deduct_late" type="checkbox" class="rounded border-gray-300" /> Deduct pay for late arrivals</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.deduct_early" type="checkbox" class="rounded border-gray-300" /> Deduct pay for early departures</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Free lates per month</label><input v-model.number="form.max_late_per_month" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Every … lates = ½ day</label><input v-model.number="form.lates_per_half_day" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-gray-400">0 = never deduct</p></div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Attendance sources</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.require_portal_punch" type="checkbox" class="rounded border-gray-300" /> Web portal punch is mandatory</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.require_machine_punch" type="checkbox" class="rounded border-gray-300" /> Machine / biometric punch is mandatory</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.geofence_required" type="checkbox" class="rounded border-gray-300" /> Portal punches must be inside an authorised office geofence</label>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Source that wins when records disagree (top first)</p>
                        <div class="mt-2 space-y-1">
                            <div v-for="s in sources" :key="s[0]" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :checked="form.source_priority.includes(s[0])" class="rounded border-gray-300" @change="toggleSource(s[0])" />
                                <span class="w-40">{{ s[1] }}</span>
                                <template v-if="form.source_priority.includes(s[0])">
                                    <span class="text-xs text-gray-400">#{{ form.source_priority.indexOf(s[0]) + 1 }}</span>
                                    <button type="button" class="px-1 text-gray-400 hover:text-gray-700" @click="moveSource(s[0], -1)">▲</button>
                                    <button type="button" class="px-1 text-gray-400 hover:text-gray-700" @click="moveSource(s[0], 1)">▼</button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div><label class="text-sm font-medium text-gray-700">Portal and machine may differ by (minutes)</label><input v-model.number="form.source_tolerance_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">When a punch-out is missing</label>
                        <select v-model="form.missing_punch_treatment" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="review">Hold for review (counts as unpaid until resolved)</option><option value="half_day">Pay a half day, flag for review</option><option value="absent">Treat as absent, flag for review</option></select>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Breaks</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Paid break (minutes)</label><input v-model.number="form.paid_break_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Maximum break (minutes)</label><input v-model.number="form.max_break_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.deduct_excess_break" type="checkbox" class="rounded border-gray-300" /> Deduct break time beyond the paid break from working hours</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.track_idle_time" type="checkbox" class="rounded border-gray-300" /> Track idle time (needs the activity agent; disclose to employees)</label>
                </section>

                <section class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">Overtime</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.overtime_eligible" type="checkbox" class="rounded border-gray-300" /> Overtime is paid</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-700">Minimum extra time (minutes)</label><input v-model.number="form.overtime_min_minutes" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="text-sm font-medium text-gray-700">Rate multiplier</label><input v-model.number="form.overtime_multiplier" type="number" step="0.25" min="1" max="5" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.overtime_requires_approval" type="checkbox" class="rounded border-gray-300" /> Overtime must be approved before it is paid</label>
                </section>
            </div>

            <p v-if="Object.keys(form.errors).length" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ Object.values(form.errors).join(" ") }}</p>

            <div class="flex justify-end gap-3">
                <Link :href="route('admin.attendance-policies.index')" class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-600">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white disabled:opacity-60">Save policy</button>
            </div>
        </form>
    </AppLayout>
</template>
