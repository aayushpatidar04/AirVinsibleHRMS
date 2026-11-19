<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Pill from "@/Components/Workforce/Pill.vue";
import { formatMinutes, punchLabels } from "@/Utils/workforce";

const props = defineProps({
    serverTime: String,
    date: String,
    actions: Array,
    lastType: String,
    punches: Array,
    record: Object,
    shift: Object,
    geofenceRequired: Boolean,
    hasPolicy: Boolean,
    locations: Array,
});

// A ticking clock based on the server's time, so a wrong device clock cannot mislead.
const offset = new Date(props.serverTime).getTime() - Date.now();
const now = ref(new Date(Date.now() + offset));
let timer;
onMounted(() => (timer = setInterval(() => (now.value = new Date(Date.now() + offset)), 1000)));
onUnmounted(() => clearInterval(timer));

const clock = computed(() => now.value.toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", second: "2-digit" }));

const busy = ref(false);
const message = ref("");
const page = usePage();
const serverError = computed(() => page.props.errors?.punch);

const buttons = {
    in: { label: "Punch in", tone: "bg-emerald-600 hover:bg-emerald-700" },
    out: { label: "Punch out", tone: "bg-red-600 hover:bg-red-700" },
    break_start: { label: "Start break", tone: "bg-amber-500 hover:bg-amber-600" },
    break_end: { label: "End break", tone: "bg-indigo-600 hover:bg-indigo-700" },
};

const locate = () =>
    new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(null);
        navigator.geolocation.getCurrentPosition(
            (p) => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude, accuracy: p.coords.accuracy }),
            () => resolve(null),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    });

const punch = async (type) => {
    if (busy.value) return;
    busy.value = true;
    message.value = "";

    let geo = null;
    if (["in", "out"].includes(type)) {
        message.value = "Checking your location…";
        geo = await locate();

        if (!geo && props.geofenceRequired) {
            message.value = "";
            busy.value = false;
            alert("Location permission is required to punch. Please allow location access in your browser and try again.");
            return;
        }
    }

    message.value = "Recording…";
    router.post(route("employee.punch.store"), { type, ...(geo ?? {}) }, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            message.value = "";
        },
    });
};
</script>

<template>
    <Head title="Punch in / out" />

    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-100">
                <p class="text-sm text-gray-500">{{ date }}</p>
                <p class="mt-1 font-mono text-5xl font-bold tracking-tight text-gray-900">{{ clock }}</p>
                <p v-if="shift" class="mt-2 text-sm text-gray-500">
                    Shift: <span class="font-medium text-gray-700">{{ shift.name }}</span> · {{ shift.start }} – {{ shift.end }}
                </p>
                <p v-else class="mt-2 text-sm text-amber-600">No shift is assigned to you yet. Ask HR.</p>

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <button
                        v-for="a in actions"
                        :key="a"
                        type="button"
                        :disabled="busy"
                        :class="['min-w-[10rem] rounded-xl px-6 py-3 text-lg font-semibold text-white shadow-sm disabled:opacity-60', buttons[a].tone]"
                        @click="punch(a)"
                    >
                        {{ buttons[a].label }}
                    </button>
                </div>

                <p v-if="message" class="mt-4 text-sm text-gray-500">{{ message }}</p>
                <p v-if="serverError" class="mt-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{{ serverError }}</p>

                <p v-if="geofenceRequired" class="mt-4 text-xs text-gray-400">
                    You must be at an authorised office to punch in or out
                    <span v-if="locations?.length">({{ locations.map((l) => `${l.name} · ${l.radius} m`).join(", ") }})</span>.
                    Allow location access when your browser asks.
                </p>
                <p v-if="!hasPolicy" class="mt-3 text-xs text-amber-600">No attendance policy applies to you yet, so attendance cannot be judged. Ask HR.</p>
            </div>

            <div v-if="record" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs text-gray-500">Status</p>
                    <div class="mt-1"><Pill :status="record.status" /></div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs text-gray-500">First in</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ record.first_in ?? "—" }}</p>
                    <p v-if="record.is_late" class="text-xs text-red-600">{{ record.late_minutes }} min late</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs text-gray-500">Worked so far</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ formatMinutes(record.worked_minutes) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs text-gray-500">Breaks</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ formatMinutes(record.break_minutes) }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h2 class="mb-3 text-sm font-semibold text-gray-800">Today's punches</h2>
                <ul v-if="punches?.length" class="divide-y divide-gray-100">
                    <li v-for="p in punches" :key="p.id" class="flex items-center justify-between py-2 text-sm">
                        <span class="font-medium text-gray-800">{{ punchLabels[p.type] }}</span>
                        <span class="text-gray-500">
                            {{ p.time }}
                            <span class="ml-2 text-xs uppercase text-gray-400">{{ p.source }}</span>
                            <span v-if="p.geo_status === 'inside'" class="ml-2 text-xs text-emerald-600">✓ location</span>
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">No punches yet today.</p>
            </div>
        </div>
    </AppLayout>
</template>
