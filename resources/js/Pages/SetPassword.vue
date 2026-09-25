<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import BrandLogo from '../Components/BrandLogo.vue';
import EmptyLayout from '../Layouts/EmptyLayout.vue';

defineOptions({
    layout: EmptyLayout,
});

const props = defineProps({
    oauthUser: {
        type: Object,
        required: true,
    },
});

const isGoogle = computed(() => props.oauthUser?.provider === 'google');
const isApple = computed(() => props.oauthUser?.provider === 'apple');

const form = useForm({
    password: '',
    password_confirm: '',
});

const submit = () => {
    form.post('/auth/set-password', {
        onError: () => {
            form.reset('password', 'password_confirm');
        },
    });
};

const cancel = () => {
    router.post('/auth/set-password/cancel');
};
</script>

<template>
    <Head>
        <title>Create Password — EBlog</title>
        <meta name="description" content="Set a password for your new E-Blog account to complete registration." />
        <meta name="robots" content="noindex, follow" />
    </Head>

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-slate-50 dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 font-sans">
        <div class="w-full max-w-md space-y-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-8 sm:p-10 shadow-xl dark:shadow-card-dark relative overflow-hidden">
            
            <!-- Top Ambient Glow -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2 relative">
                <div class="flex justify-center mb-3">
                    <BrandLogo size="lg" :showText="false" />
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white tracking-tight">
                    Set Account Password
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Create a password so you can also log in directly using your email
                </p>
            </div>

            <!-- OAuth Profile Connected Card -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center shrink-0 shadow-sm">
                    <!-- Google Icon -->
                    <svg v-if="isGoogle" class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                        <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.6h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.9z"/>
                        <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15.2s.7 5.5 1.9 7.9l3.7-2.9z"/>
                        <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16.5C3.7 20.2 7.5 23.5 12 23.5z"/>
                    </svg>
                    <!-- Apple Icon -->
                    <svg v-else class="w-5 h-5 fill-current text-slate-900 dark:text-white" viewBox="0 0 170 170">
                        <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.57-7.71-11.62-14.01-6.19-9.57-11.06-20.2-14.61-31.9-3.55-11.69-5.33-22.95-5.33-33.78 0-14.28 3.59-26.17 10.77-35.66 7.18-9.48 16.14-14.29 26.89-14.42 5.11 0 10.75 1.41 16.92 4.22 6.17 2.82 10.15 4.27 11.94 4.35 1.57 0 5.75-1.55 12.55-4.66 6.8-3.09 12.73-4.44 17.78-4.04 13.79.98 24.36 5.86 31.7 14.65-12.19 7.39-18.18 17.52-17.97 30.39.22 10.23 4.1 18.77 11.65 25.61 7.55 6.84 16.63 10.7 27.24 11.58-2.61 7.84-5.99 15.68-10.14 23.52zM119.22 33.15c0-7.39 2.64-14.34 7.92-20.85 5.28-6.51 11.83-10.77 19.65-12.8 1.09 7.18-.76 14.23-5.56 21.15-4.79 6.92-11.23 11.02-19.32 12.3-.54-.6-.94-1.22-1.2-1.85-.99-2.61-1.49-5.26-1.49-7.95z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                            {{ oauthUser?.name }}
                        </span>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-md bg-amber-500/10 text-amber-500">
                            {{ isGoogle ? 'Google' : 'Apple' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{ oauthUser?.email }}
                    </p>
                </div>
            </div>

            <!-- Global Error Banner -->
            <div v-if="$page.props.errors?.invalid || $page.props.errors?.error || form.errors?.invalid || form.errors?.error || $page.props.flash?.error" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-medium text-center">
                {{ $page.props.errors?.invalid || $page.props.errors?.error || form.errors?.invalid || form.errors?.error || $page.props.flash?.error }}
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Create Password
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
                        Confirm Password
                    </label>
                    <input
                        id="password_confirm"
                        v-model="form.password_confirm"
                        type="password"
                        placeholder="Repeat your password"
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
                    <span>{{ form.processing ? 'Saving...' : 'Complete & Sign In' }}</span>
                </button>

                <div class="text-center pt-2">
                    <button
                        type="button"
                        @click="cancel"
                        class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                    >
                        ← Cancel and return to Sign In
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
