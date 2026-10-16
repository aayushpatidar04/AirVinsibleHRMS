<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
    currency: {
        type: String,
        default: "INR",
    },
});

function money(value, currency) {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}
</script>

<template>
    <section v-if="items.length" class="border-b border-slate-200 px-5 py-5 last:border-b-0 sm:px-6">
        <h3 class="mb-3 text-sm font-bold text-slate-900">{{ title }}</h3>
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="hidden grid-cols-[minmax(0,1fr)_auto_auto] gap-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:grid">
                <span>Component</span>
                <span class="text-right min-w-32">Monthly</span>
                <span class="text-right min-w-32">Annual</span>
            </div>
            <div
                v-for="item in items"
                :key="item.id ?? item.name"
                class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 px-4 py-3 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto]"
            >
                <div class="col-span-2 min-w-0 sm:col-span-1">
                    <p class="break-words font-medium text-slate-800">{{ item.name }}</p>
                    <p v-if="item.description" class="mt-0.5 text-xs leading-5 text-slate-500">{{ item.description }}</p>
                </div>
                <div class="flex justify-between gap-2 text-slate-700 sm:block sm:whitespace-nowrap sm:text-right min-w-32">
                    <span class="text-xs text-slate-500 sm:hidden">Monthly</span>
                    <span>{{ money(item.monthly_amount, currency) }}</span>
                </div>
                <div class="flex justify-between gap-2 font-medium text-slate-900 sm:block sm:whitespace-nowrap sm:text-right min-w-32">
                    <span class="text-xs font-normal text-slate-500 sm:hidden">Annual</span>
                    <span>{{ money(item.annual_amount, currency) }}</span>
                </div>
            </div>
        </div>
    </section>
</template>
