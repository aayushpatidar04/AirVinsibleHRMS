<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { ref, computed } from "vue";

const page = usePage();

const props = defineProps({
    email: { type: String, required: true },
});

const otp = ref("");
const submitting = ref(false);
const errors = ref({});
const resent = ref(false);

const submit = () => {
    submitting.value = true;
    errors.value = {};
    router.post(
        route("candidate.documents.verify-otp.submit"),
        { email: props.email, otp: otp.value },
        {
            preserveScroll: true,
            onError: (e) => { errors.value = e; submitting.value = false; },
            onSuccess: () => { submitting.value = false; },
        }
    );
};

const resend = () => {
    resent.value = true;
    router.post(
        route("candidate.documents.resend-otp"),
        { email: props.email },
        {
            preserveScroll: true,
            preserveState: true,
            Finch: () => { resent.value = false; },
        }
    );
};
</script>

<template>
    <PublicLayout>
        <Head title="Verify Email" />
        <div class="mx-auto max-w-md space-y-6 py-12">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-ink">Verify Your Email</h1>
                <p class="mt-1 text-sm text-ink-mute">
                    We sent a 6-digit verification code to <strong>{{ email }}</strong>.
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="label">Verification Code</label>
                        <input
                            v-model="otp"
                            type="text"
                            maxlength="6"
                            inputmode="numeric"
                            class="input-field text-center text-2xl tracking-[0.5em]"
                            placeholder="123456"
                            autofocus
                        />
                        <p v-if="errors.otp" class="error">{{ errors.otp }}</p>
                    </div>

                    <button type="submit" :disabled="submitting" class="btn-primary w-full">
                        {{ submitting ? 'Verifying...' : 'Verify & Continue' }}
                    </button>

                    <div class="text-center text-sm">
                        <span class="text-ink-mute">Didn't receive the code? </span>
                        <button
                            v-if="!resent"
                            type="button"
                            @click="resend"
                            class="text-indigo-600 hover:text-indigo-900 font-medium"
                        >
                            Resend
                        </button>
                        <span v-else class="text-green-600">Code sent!</span>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>