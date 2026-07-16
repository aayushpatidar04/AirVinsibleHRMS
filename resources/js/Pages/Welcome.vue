<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({ canLogin: Boolean })
</script>

<template>

    <Head title="HRMS — Human Resource Management System" />

    <div
        class="min-h-screen bg-gradient-to-br from-indigo-900 via-indigo-800 to-indigo-700 flex flex-col items-center justify-center px-6">
        <!-- Logo -->
        <div class="text-center mb-12">
            <div
                class="inline-flex items-center justify-center h-20 w-20 rounded-3xl bg-white/10 text-white text-4xl mb-6 shadow-xl backdrop-blur-sm">
                ⚡</div>
            <h1 class="text-5xl font-extrabold text-white tracking-tight mb-3">HRMS</h1>
            <p class="text-indigo-200 text-xl font-light">Human Resource Management System</p>
            <p class="text-indigo-300 text-sm mt-2">Recruitment · Interviews · Payroll</p>
        </div>

        <!-- Feature Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl w-full mb-10">
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center border border-white/20">
                <p class="text-3xl mb-2">📱</p>
                <p class="font-semibold text-white">QR Registration</p>
                <p class="text-indigo-300 text-xs mt-1">Candidates register via QR code scan</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center border border-white/20">
                <p class="text-3xl mb-2">🎙️</p>
                <p class="font-semibold text-white">Interview Rounds</p>
                <p class="text-indigo-300 text-xs mt-1">Dynamic multi-round interview process</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 text-center border border-white/20">
                <p class="text-3xl mb-2">💰</p>
                <p class="font-semibold text-white">Salary Offers</p>
                <p class="text-indigo-300 text-xs mt-1">Smart salary logic by profile type</p>
            </div>
        </div>

        <!-- CTA -->
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <Link :href="route('login')"
                class="inline-flex items-center gap-2 bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition shadow-lg text-sm">
                Login to Dashboard →
            </Link>
        </div>

        <p class="text-indigo-400 text-xs mt-12">© {{ new Date().getFullYear() }} HRMS. All rights reserved.</p>
    </div>
</template>