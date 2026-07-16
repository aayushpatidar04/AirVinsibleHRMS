<script setup>
// Candidate/Success.vue
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'
defineProps({ candidate_id: Number, name: String, position: String, branch: String })
</script>

<template>
    <GuestLayout>

        <Head title="Registration Successful – HRMS" />
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-10">
                <div class="text-6xl mb-4">🎉</div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Registration Successful!</h1>
                <p class="text-gray-600 mb-6">
                    Thank you, <strong>{{ name }}</strong>! Your registration for
                    <strong>{{ position }}</strong> at <strong>{{ branch }}</strong> has been received.
                </p>
                <div
                    class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm text-indigo-800 text-left mb-6">
                    <p class="font-semibold mb-2">What happens next?</p>
                    <ol class="space-y-1 list-decimal list-inside text-indigo-700">
                        <li>Our team will review your profile</li>
                        <li>An interviewer will be assigned to you shortly</li>
                        <li>Your interview will begin at the venue</li>
                        <li>You'll be notified of the outcome after all rounds</li>
                    </ol>
                </div>
                <p class="text-xs text-gray-400">
                    Your registration ID: <strong class="text-gray-600">#{{ candidate_id }}</strong>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>