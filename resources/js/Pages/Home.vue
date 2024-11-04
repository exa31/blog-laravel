<script setup>
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import CardBlog from '../Components/cardBlog.vue';
const props = usePage().props.posts;
</script>

<template>

    <Head>
        <title>Home</title>
    </Head>
    <div class="flex space-x-8">
        <div v-if="props.length === 0">
            <p class="text-gray-700 dark:text-gray-400">No posts found.</p>
        </div>
        <CardBlog v-else v-for="(prop, index) in props" :key="index" :post="prop" />
    </div>


</template>