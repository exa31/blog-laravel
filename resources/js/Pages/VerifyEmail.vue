<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import BrandLogo from '../Components/BrandLogo.vue';
import EmptyLayout from '../Layouts/EmptyLayout.vue';
import ToastNotification from '../Components/ToastNotification.vue';
import { showToast } from '../helper/toast';

defineOptions({
    layout: EmptyLayout,
});

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
});

const digits = reactive(['', '', '', '', '', '']);
const digitInputs = ref([]);
const isResending = ref(false);
const resendCooldown = ref(0);
let cooldownTimer = null;

const form = useForm({
    email: props.email,
    code: '',
});

const fullCode = computed(() => digits.join(''));

const startCooldown = (seconds = 60) => {
    resendCooldown.value = seconds;
    if (cooldownTimer) clearInterval(cooldownTimer);
    cooldownTimer = setInterval(() => {
        if (resendCooldown.value > 0) {
            resendCooldown.value--;
        } else {
            clearInterval(cooldownTimer);
        }
    }, 1000);
};

onMounted(() => {
    // Focus first input box on mount
    digitInputs.value[0]?.focus();
    startCooldown(60);
});

onUnmounted(() => {
    if (cooldownTimer) clearInterval(cooldownTimer);
});

const handleInput = (index, event) => {
    const val = event.target.value.replace(/\D/g, '');
    digits[index] = val ? val.slice(-1) : '';

    if (val && index < 5) {
        digitInputs.value[index + 1]?.focus();
    }

    form.code = digits.join('');
    if (form.code.length === 6) {
        submit();
    }
};

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !digits[index] && index > 0) {
        digits[index - 1] = '';
        digitInputs.value[index - 1]?.focus();
    }
};

const handlePaste = (event) => {
    event.preventDefault();
    const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    if (!pasted) return;

    for (let i = 0; i < 6; i++) {
        digits[i] = pasted[i] || '';
    }

    form.code = digits.join('');
    const targetIdx = Math.min(pasted.length, 5);
    digitInputs.value[targetIdx]?.focus();

    if (pasted.length === 6) {
        submit();
    }
};

const submit = () => {
    form.code = digits.join('');
    if (form.code.length !== 6) {
        showToast('Please enter all 6 digits of your verification code.', 'error');
        return;
    }

    form.post('/verify-email', {
        onError: () => {
            // Reset digits on error and refocus first box
            for (let i = 0; i < 6; i++) digits[i] = '';
            digitInputs.value[0]?.focus();
        },
    });
};

const resendCode = async () => {
    if (isResending.value || resendCooldown.value > 0) return;

    isResending.value = true;
    try {
        const res = await axios.post('/verify-email/resend', {
            email: props.email,
        });
        showToast(res.data?.message || 'A new verification code has been sent to your email.', 'success');
        startCooldown(60);
    } catch (err) {
        showToast(err.response?.data?.message || err.response?.data?.errors?.invalid || 'Failed to resend code.', 'error');
    } finally {
        isResending.value = false;
    }
};
</script>

<template>
    <Head>
        <title>Verify Your Email — EBlog</title>
        <meta name="description" content="Verify your email address to activate your E-Blog account." />
        <meta name="robots" content="noindex, follow" />
    </Head>

    <ToastNotification />

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-slate-50 dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 font-sans">
        <div class="w-full max-w-md space-y-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-8 sm:p-10 shadow-xl dark:shadow-card-dark relative overflow-hidden">
            
            <!-- Ambient Glow -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2 relative">
                <div class="flex justify-center mb-3">
                    <BrandLogo size="lg" :showText="false" />
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white tracking-tight">
                    Verify Your Email
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                    We sent a 6-digit confirmation code to
                    <strong class="text-slate-900 dark:text-slate-200 break-all block mt-0.5">{{ props.email }}</strong>
                </p>
            </div>

            <!-- Global Error Banner -->
            <div v-if="form.errors.invalid || form.errors.code" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-medium text-center">
                {{ form.errors.invalid || form.errors.code }}
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- 6 Discrete Digit Boxes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-3 text-center">
                        Enter 6-Digit Code
                    </label>
                    <div class="flex items-center justify-center gap-2 sm:gap-3" @paste="handlePaste">
                        <input
                            v-for="(digit, idx) in digits"
                            :key="idx"
                            :ref="el => digitInputs[idx] = el"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="1"
                            :value="digit"
                            @input="e => handleInput(idx, e)"
                            @keydown="e => handleKeyDown(idx, e)"
                            class="w-11 h-13 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-mono font-bold rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all"
                        />
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing || fullCode.length !== 6"
                    class="w-full py-3.5 px-4 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all shadow-glow font-display active:scale-[0.98] flex items-center justify-center gap-2"
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Verifying Code...' : 'Verify & Activate Account' }}</span>
                </button>

                <!-- Resend & Alternative Actions -->
                <div class="flex flex-col items-center gap-3 pt-2 text-xs text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <span>Didn't receive the email?</span>
                        <button
                            type="button"
                            @click="resendCode"
                            :disabled="isResending || resendCooldown > 0"
                            class="font-semibold text-amber-500 hover:text-amber-400 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ isResending ? 'Sending...' : (resendCooldown > 0 ? `Resend code in ${resendCooldown}s` : 'Resend code') }}
                        </button>
                    </div>

                    <Link href="/register" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        ← Use a different email address
                    </Link>
                </div>
            </form>

        </div>
    </div>
</template>
