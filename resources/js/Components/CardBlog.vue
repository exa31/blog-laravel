<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps(['post', 'save']);
const emits = defineEmits(['deleteSave', 'save']);

const isSaved = computed(() => {
    return props.save.some(post => post.post_id === props.post.id);
});

const save = (id) => {
    emits('save', id);
}

const deleteSave = (id) => {
    emits('deleteSave', id);
}

</script>

<template>
    <div class="flex flex-col bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <div class="grow">
            <img class="object-contain mx-auto rounded-t-lg h-44" :src="'storage/' + props.post.image_thumbnail"
                :alt="props.post.title" />
        </div>
        <div class="p-5 ">
            <div class="flex items-center space-x-1">
                <div
                    class="relative inline-flex items-center justify-center w-6 h-6 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.post.user.avatar }}</span>
                </div>
                <h5 class="text-xs text-gray-500">{{ props.post.user.name }}</h5>
            </div>
            <div class="flex-1">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ props.post.title }}
                </h5>
                <article v-html="props.post.content"
                    class="mb-3 font-normal text-gray-700 line-clamp-3 dark:text-gray-400">
                </article>
            </div>
            <div class="flex justify-between">
                <Link :href="'/post/' + props.post.slug"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                Read more
                <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 14 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M1 5h12m0 0L9 1m4 4L9 9" />
                </svg>
                </Link>
                <svg v-if="isSaved" @click="() => deleteSave(props.post.id)"
                    class="w-6 h-6 text-gray-800 hover:cursor-pointer dark:text-white" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M7.833 2c-.507 0-.98.216-1.318.576A1.92 1.92 0 0 0 6 3.89V21a1 1 0 0 0 1.625.78L12 18.28l4.375 3.5A1 1 0 0 0 18 21V3.889c0-.481-.178-.954-.515-1.313A1.808 1.808 0 0 0 16.167 2H7.833Z" />
                </svg>
                <svg v-else @click="() => save(props.post.id)"
                    class="w-6 h-6 text-gray-800 hover:cursor-pointer dark:text-white" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m17 21-5-4-5 4V3.889a.92.92 0 0 1 .244-.629.808.808 0 0 1 .59-.26h8.333a.81.81 0 0 1 .589.26.92.92 0 0 1 .244.63V21Z" />
                </svg>
            </div>
        </div>
    </div>
</template>