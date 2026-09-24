<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import BrandLogo from '../Components/BrandLogo.vue';
import EmptyLayout from '../Layouts/EmptyLayout.vue';
import ToastNotification from '../Components/ToastNotification.vue';

defineOptions({
    layout: EmptyLayout,
});

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirm: '',
});

const submit = () => {
    form.post('/reset-password', {
        onError: () => {
            form.reset('password', 'password_confirm');
        },
    });
};
</script>

<template>
    <Head>
        <title>Set New Password — EBlog</title>
        <meta name="description" content="Set a new secure password for your E-Blog account." />
        <meta name="robots" content="noindex, follow" />
    </Head>

    <ToastNotification />

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-slate-50 dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 font-sans">
        <div class="w-full max-w-md space-y-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-8 sm:p-10 shadow-xl dark:shadow-card-dark relative overflow-hidden">
            
            <!-- Top Ambient Glow -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2 relative">
                <div class="flex justify-center mb-3">
                    <BrandLogo size="lg" :showText="false" />
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white tracking-tight">
                    Set New Password
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Create a strong and unique password for your account
                </p>
            </div>

            <!-- Global Error Banner -->
            <div v-if="form.errors.invalid" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-medium text-center">
                {{ form.errors.invalid }}
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
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

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        New Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="At least 8 characters"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all"
                    />
                    <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label for="password_confirm" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Confirm New Password
                    </label>
                    <input
                        id="password_confirm"
                        v-model="form.password_confirm"
                        type="password"
                        placeholder="Repeat your new password"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all"
                    />
                    <p v-if="form.errors.password_confirm" class="text-xs text-rose-500 mt-1">{{ form.errors.password_confirm }}</p>
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
                    <span>{{ form.processing ? 'Resetting Password...' : 'Save & Log In' }}</span>
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
