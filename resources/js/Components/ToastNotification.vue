<script setup>
import { toasts, removeToast } from '../helper/toast';
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none max-w-sm w-full">
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-center justify-between p-4 rounded-xl shadow-lg border backdrop-blur-md transition-all duration-200"
                :class="[
                    toast.type === 'error'
                        ? 'bg-rose-900/90 text-white border-rose-700/50'
                        : 'bg-slate-900/95 text-white border-slate-700/60 dark:bg-slate-800/95 dark:border-slate-600/60'
                ]"
            >
                <div class="flex items-center gap-3">
                    <span v-if="toast.type !== 'error'" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span v-else class="w-2 h-2 rounded-full bg-rose-400"></span>
                    <p class="text-sm font-medium">{{ toast.message }}</p>
                </div>
                <button
                    @click="removeToast(toast.id)"
                    class="ml-4 text-slate-400 hover:text-white p-1 rounded-lg transition-colors focus:outline-none"
                    aria-label="Close"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
