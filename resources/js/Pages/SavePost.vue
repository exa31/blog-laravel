<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import CardBlog from '../Components/CardBlog.vue';
import { onMounted } from 'vue';

const props = usePage().props;

onMounted(() => {
    document.addEventListener('scroll', handleScroll);
});

const handleScroll = async (e) => {
    if ((e.target.documentElement.clientHeight + e.target.documentElement.scrollTop) === e.target.documentElement.scrollHeight) {
        if (props.posts.length === props.totalPosts) {
            return;
        }
        axios.get(`/save-posts/on-scroll?skip=${props.posts.length}`).then(res => {
            props.posts.push(...res.data.posts);
        }).catch(err => {
            console.log(err);
        });
    }
}

const deleteSave = (id) => {
    axios.delete('/save-post/' + id)
        .then(response => {
            props.posts = props.posts.filter(post => post.post_id !== id);
            props.totalPosts = props.totalPosts - 1;
        })
        .catch(error => {
            console.log(error);
        });
}
</script>

<template>
    <h1 class="mb-4 text-2xl font-bold">{{ props.posts.length }} of {{ props.totalPosts }} Posts</h1>
    <div v-if="props.posts.length === 0">
        <p class="text-center text-gray-700 dark:text-gray-400">
            No saved posts found. <br>
            <span class="text-sm text-gray-500">Please check back later or try saving a different post.</span>
        </p>
        <div class="mt-4 text-center">
            <Link href="/" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
            Go to Home
            </Link>
        </div>
    </div>
    <div @scroll="handleScroll" v-else class="grid gap-8 mx-auto sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
        <CardBlog v-for="(post, index) in props.posts" :key="index" :post="post.post" @deleteSave="deleteSave" />
    </div>
</template>