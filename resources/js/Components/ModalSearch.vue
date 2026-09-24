<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps(['openSearch']);
const emits = defineEmits(['update:openSearch', 'submitSearch']);

const searchInput = ref(null);
const searchTerm = ref('');

watch(() => props.openSearch, (isOpen) => {
    if (isOpen) {
        setTimeout(() => {
            searchInput.value?.focus();
        }, 50);
    }
});

const close = () => {
    emits('update:openSearch', false);
};

const handleSubmit = (e) => {
    e.preventDefault();
    if (searchTerm.value.trim()) {
        emits('submitSearch', searchTerm.value.trim());
        close();
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && props.openSearch) {
        close();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="props.openSearch"
                class="fixed inset-0 z-50 flex items-start justify-center pt-20 sm:pt-28 px-4 bg-slate-900/60 backdrop-blur-sm"
                @click.self="close"
            >
                <div class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
                    <form @submit="handleSubmit" class="relative flex items-center p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800/80">
                        <svg class="w-5 h-5 text-slate-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchInput"
                            v-model="searchTerm"
                            type="text"
                            placeholder="Search articles, topics, or authors..."
                            class="w-full px-4 py-2 text-base text-slate-900 dark:text-white placeholder-slate-400 bg-transparent border-none outline-none focus:ring-0"
                        />
                        <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-sm">
                            ESC
                        </kbd>
                    </form>

                    <div class="p-4 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                        <span>Press <strong class="font-medium text-slate-700 dark:text-slate-300">Enter</strong> to search</span>
                        <button
                            type="button"
                            @click="close"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>