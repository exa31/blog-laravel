<script setup>
const emits = defineEmits(['close']);
const props = defineProps(['countComment', 'totalComments']);
const close = () => {
    emits('close');
}
</script>

<template>
    <div class="flex items-center justify-between px-8 mt-4">
        <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white">Comments ({{ props.totalComments }})
        </h1>
        <svg @click="close" class="w-6 h-6 text-gray-800 hover:cursor-pointer dark:text-white" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
    </div>
</template>