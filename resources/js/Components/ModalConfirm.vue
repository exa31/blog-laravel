<script setup>
import { onMounted, onBeforeUnmount, watch } from 'vue';
import { confirmState, handleConfirmResponse } from '../helper/confirm';

const confirm = () => {
    handleConfirmResponse(true);
};

const cancel = () => {
    handleConfirmResponse(false);
};

const handleKeyDown = (e) => {
    if (!confirmState.value.isOpen) return;

    if (e.key === 'Escape') {
        e.preventDefault();
        cancel();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        confirm();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="confirmState.isOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md select-none"
            @click.self="cancel"
        >
            <div
                class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl shadow-2xl p-6 sm:p-7 overflow-hidden animate-scale-up"
            >
                <!-- Decorative Top Glow Line -->
                <div
                    class="absolute top-0 inset-x-0 h-1"
                    :class="[
                        confirmState.type === 'danger'
                            ? 'bg-gradient-to-r from-rose-500 via-rose-400 to-rose-500'
                            : confirmState.type === 'warning'
                            ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500'
                            : 'bg-gradient-to-r from-sky-500 via-indigo-500 to-sky-500'
                    ]"
                ></div>

                <div class="flex items-start gap-4">
                    <!-- Icon Badge -->
                    <div
                        class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border"
                        :class="[
                            confirmState.type === 'danger'
                                ? 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                : confirmState.type === 'warning'
                                ? 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                : 'bg-sky-500/10 text-sky-500 border-sky-500/20'
                        ]"
                    >
                        <!-- Danger / Trash / Alert Icon -->
                        <svg
                            v-if="confirmState.type === 'danger'"
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>

                        <!-- Warning / Exclamation Triangle Icon -->
                        <svg
                            v-else-if="confirmState.type === 'warning'"
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>

                        <!-- Info / Question Icon -->
                        <svg
                            v-else
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>

                    <!-- Text Info -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h3 class="text-base font-bold font-display text-slate-900 dark:text-white leading-tight">
                            {{ confirmState.title }}
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                            {{ confirmState.message }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                    <button
                        type="button"
                        @click="cancel"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all font-display"
                    >
                        {{ confirmState.cancelText }}
                    </button>

                    <button
                        type="button"
                        @click="confirm"
                        class="px-5 py-2 text-xs font-semibold rounded-xl transition-all shadow-sm active:scale-95 font-display"
                        :class="[
                            confirmState.type === 'danger'
                                ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-500/20'
                                : confirmState.type === 'warning'
                                ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20'
                                : 'bg-sky-500 hover:bg-sky-400 text-white shadow-sky-500/20'
                        ]"
                    >
                        {{ confirmState.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
