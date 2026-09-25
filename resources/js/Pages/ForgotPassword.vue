<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BrandLogo from '../Components/BrandLogo.vue';
import EmptyLayout from '../Layouts/EmptyLayout.vue';

defineOptions({
    layout: EmptyLayout,
});

const form = useForm({
    email: '',
});

const isSubmitted = ref(false);

const submit = () => {
    form.post('/forgot-password', {
        onSuccess: () => {
            isSubmitted.value = true;
        },
    });
};
</script>

<template>
    <Head>
        <title>Forgot Password — EBlog</title>
        <meta name="description" content="Reset your E-Blog account password easily using your registered email address." />
        <meta name="robots" content="noindex, follow" />
    </Head>

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-slate-50 dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 font-sans">
        <div class="w-full max-w-md space-y-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-8 sm:p-10 shadow-xl dark:shadow-card-dark relative overflow-hidden">
            
            <!-- Top Ambient Glow -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2 relative">
                <div class="flex justify-center mb-3">
                    <BrandLogo size="lg" :showText="false" />
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white tracking-tight">
                    Reset Password
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Enter your email address to receive a secure password reset link via Resend.
                </p>
            </div>

            <!-- Success State Banner -->
            <div v-if="isSubmitted" class="space-y-6">
                <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-sm space-y-2 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-500 mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-white">Check your email</p>
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        We sent instructions to <strong class="text-emerald-500">{{ form.email }}</strong>. Please click the link inside to set a new password.
                    </p>
                </div>

                <div class="text-center space-y-3">
                    <button
                        @click="isSubmitted = false"
                        type="button"
                        class="text-xs font-semibold text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                    >
                        Did not receive the email? Try again
                    </button>
                    <div>
                        <Link href="/login" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                            ← Return to Sign In
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Request Form -->
            <form v-else @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Email Address
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="you@example.com"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all"
                    />
                    <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 rounded-xl transition-all shadow-glow font-display active:scale-[0.98] mt-2 flex items-center justify-center gap-2"
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Sending Email...' : 'Send Reset Link' }}</span>
                </button>

                <div class="text-center pt-2">
                    <Link href="/login" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        ← Back to Sign In
                    </Link>
                </div>
            </form>

        </div>
    </div>
</template>
