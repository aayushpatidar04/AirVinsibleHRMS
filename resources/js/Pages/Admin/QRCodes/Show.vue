<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import QrcodeVue from "qrcode.vue";

const props = defineProps({ qrCode: Object });

const toggle = () =>
    router.post(route("admin.qrcodes.toggle", props.qrCode.id));
const del = () => {
    if (confirm("Delete this QR code?"))
        router.delete(route("admin.qrcodes.destroy", props.qrCode.id));
};
const copyUrl = () => {
    navigator.clipboard.writeText(props.qrCode.url);
    alert("URL copied!");
};
const print = () => {
    setTimeout(() => window.print(), 300);
};
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.qrcodes.index')"
                        class="text-gray-400 hover:text-gray-600"
                        >←</Link
                    >
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ qrCode.label }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <button @click="toggle" class="btn-secondary text-sm">
                        {{ qrCode.is_active ? "Deactivate" : "Activate" }}
                    </button>
                    <button @click="del" class="btn-danger text-sm">
                        Delete
                    </button>
                </div>
            </div>

            <!-- QR Card -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center"
                id="qr-print-area"
            >
                <p class="text-xl font-bold text-gray-900 mb-1">
                    {{ qrCode.label }}
                </p>
                <p class="text-sm text-gray-500 mb-6">
                    {{ qrCode.branch }} · {{ qrCode.form }}
                </p>

                <!-- Local QR rendering -->
                <div
                    class="inline-block p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-inner mb-6"
                >
                    <QrcodeVue :value="qrCode.url" :size="224" level="H" />
                </div>

                <p class="text-xs text-gray-400 mb-4 font-mono break-all px-4">
                    {{ qrCode.url }}
                </p>

                <p class="text-sm font-semibold text-gray-700">
                    Scan to register for interview
                </p>
                <p v-if="qrCode.expiry" class="text-xs text-orange-500 mt-1">
                    Expires: {{ qrCode.expiry }}
                </p>
            </div>

            <!-- Info -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-center"
            >
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <span
                        :class="
                            qrCode.is_active && !qrCode.is_expired
                                ? 'text-green-600'
                                : 'text-red-500'
                        "
                        class="font-semibold"
                    >
                        {{
                            qrCode.is_expired
                                ? "Expired"
                                : qrCode.is_active
                                  ? "Active"
                                  : "Inactive"
                        }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Times Used</p>
                    <p class="font-bold text-indigo-600 text-xl">
                        {{ qrCode.usage_count }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Branch</p>
                    <p class="font-medium text-gray-700">{{ qrCode.branch }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Form</p>
                    <p class="font-medium text-gray-700">{{ qrCode.form }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 flex-wrap">
                <button @click="copyUrl" class="btn-secondary flex-1">
                    📋 Copy Registration URL
                </button>
                <button @click="print" class="btn-primary flex-1">
                    🖨️ Print QR Code
                </button>
            </div>

            <!-- Registration URL -->
            <div
                class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm text-indigo-800"
            >
                <p class="font-semibold mb-1">Direct Registration URL</p>
                <a
                    :href="qrCode.url"
                    target="_blank"
                    class="break-all text-indigo-600 hover:underline text-xs"
                    >{{ qrCode.url }}</a
                >
                <p class="text-xs text-indigo-500 mt-2">
                    Share this link or print the QR code to let candidates
                    self-register.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden;
    }

    /* Show the print area and all its children */
    #qr-print-area,
    #qr-print-area * {
        visibility: visible;
    }

    /* Position the print area at the top-left for printing */
    #qr-print-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
}
</style>
