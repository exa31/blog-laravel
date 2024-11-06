<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps(
    {
        post: Object
    }
)

</script>

<template>
    <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <img class="object-cover rounded-t-lg h-44" :src="'storage/' + props.post.image_thumbnail"
            :alt="props.post.title" />
        <div class="p-5">
            <div class="flex items-center space-x-1">
                <div
                    class="relative inline-flex items-center justify-center w-6 h-6 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.post.user.avatar }}</span>
                </div>
                <h5 class="text-xs text-gray-500">{{ props.post.user.name }}</h5>
            </div>
            <a href="#">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ props.post.title }}
                </h5>
            </a>
            <article v-html="props.post.content" class="mb-3 font-normal text-gray-700 line-clamp-3 dark:text-gray-400">
            </article>
            <Link :href="'/post/' + props.post.slug"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            Read more
            <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 14 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M1 5h12m0 0L9 1m4 4L9 9" />
            </svg>
            </Link>
        </div>
    </div>
</template>