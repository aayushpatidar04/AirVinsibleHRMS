<script setup>
import { ref, watch, onMounted } from "vue";

const props = defineProps({
    title: String,
    value: { type: [Number, String], default: 0 },
    icon: String,
    color: { type: String, default: "indigo" },
    subtitle: String,
    delay: { type: Number, default: 0 },
});

const palette = {
    indigo: "bg-indigo-50 text-indigo-600",
    blue: "bg-sky-50 text-sky-600",
    teal: "bg-teal-50 text-teal-600",
    yellow: "bg-amber-50 text-amber-600",
    green: "bg-emerald-50 text-emerald-600",
    red: "bg-red-50 text-red-600",
    purple: "bg-violet-50 text-violet-600",
    gray: "bg-slate-100 text-slate-600",
};

const isNumeric = (v) => v !== null && v !== "" && Number.isFinite(Number(v));
const shown = ref(isNumeric(props.value) ? 0 : props.value);

const run = () => {
    if (!isNumeric(props.value)) return (shown.value = props.value);
    const to = Number(props.value);
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduce || to === 0) return (shown.value = to);
    const start = performance.now();
    const tick = (now) => {
        const p = Math.min((now - start) / 800, 1);
        shown.value = Math.round(to * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
};

onMounted(run);
watch(() => props.value, run);
</script>

<template>
    <div class="surface animate-fade-up p-4 transition-shadow hover:shadow-lift sm:p-5"
        :style="{ animationDelay: `${delay}ms` }">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-ink-soft">{{ title }}</p>
            <span :class="[palette[color] ?? palette.indigo, 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base']">
                {{ icon }}
            </span>
        </div>
        <p class="mt-3 font-display text-3xl font-bold tracking-tight text-ink">{{ shown }}</p>
        <p v-if="subtitle" class="mt-1 text-xs text-ink-mute">{{ subtitle }}</p>
    </div>
</template>