<script setup>
const emits = defineEmits(['close']);
const props = defineProps(['countComment', 'totalComments']);
</script>

<template>
    <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold font-display text-slate-900 dark:text-white">
                Responses ({{ props.totalComments ?? 0 }})
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Community thoughts and discussion</p>
        </div>
        
        <button
            @click="emits('close')"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="Close Comments"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>