<script setup>
import {router, usePage} from '@inertiajs/vue3';
import CardBlog from '../Components/CardBlog.vue';
import { onMounted } from 'vue';
import axios from 'axios';
const props = usePage().props;

const handleScroll = async (e) => {
    if ((e.target.documentElement.clientHeight + e.target.documentElement.scrollTop) === e.target.documentElement.scrollHeight) {
        if (props.posts.length === props.totalPosts) {
            return;
        }
        axios.get(`/posts/on-scroll?skip=${props.posts.length}&q=${props.query}`).then(res => {
            props.posts.push(...res.data.posts);
        }).catch(err => {
            console.log(err);
        });
    }
}

onMounted(() => {
    document.addEventListener('scroll', handleScroll);
});

const save = (id) => {
    if (!props.isLogin) {
        router.visit('/login');
        return;
    }
    axios.post('/save-post', {
        post_id: id
    })
        .then(response => {
            props.savedPosts.push({ post_id: id });

        })
        .catch(error => {
            console.log(error);
        });
}

const deleteSave = (id) => {
    axios.delete('/save-post/' + id)
        .then(response => {
            props.savedPosts = props.savedPosts.filter(post => post.post_id !== id);
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
            No posts found. <br>
            <span class="text-sm text-gray-500">Please check back later or try a different search.</span>
        </p>
    </div>
    <div @scroll="handleScroll" v-else class="grid gap-8 mx-auto sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
        <CardBlog v-for="(post, index) in props.posts" :key="index" :post="post" :save="props.savedPosts"
            @deleteSave="deleteSave" @save="save" />
    </div>
</template>
