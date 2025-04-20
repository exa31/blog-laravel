<script setup>
import { usePage, Head, router } from '@inertiajs/vue3';
import { formatDateTime } from '../helper/index';
import SidebarComments from '../Components/SidebarComments.vue';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = usePage().props;
const handleLike = async () => {
    if (props.isLogin) {
        const res = await axios.post('/like/' + props.post.id, { id: props.post.id });
        props.isLiked = !props.isLiked;
        return props.post.like = res.data.like;
    }
    router.visit('/login');
    return;
}

const sideComment = ref(false);

const handleComment = (e) => {
    e.stopPropagation();
    if (props.isLogin) {
        return sideComment.value = !sideComment.value;
    }
    router.visit('/login');
    return;
}
const addComments = (comments) => {
    props.post.comments.unshift(comments);
    props.totalComments++;
}

const updateComments = (comments) => {
    props.post.comments.push(comments);
}

const copyLinkPost = () => {
    navigator.clipboard.writeText(window.location.href);
    alert('Link copied to clipboard');
}

const save = (id) => {
    if (!props.isLogin) {
        router.visit('/login');
        return;
    }
    axios.post('/save-post', {
        post_id: id
    })
        .then(response => {
            props.isSaved = true;

        })
        .catch(error => {
            console.log(error);
        });
}

const deleteSave = (id) => {
    axios.delete('/save-post/' + id)
        .then(response => {
            props.isSaved = false;
        })
        .catch(error => {
            console.log(error);
        });
}

const titleHead = computed(() => {
    const propsTitle = props.post.slug;
    const title = propsTitle.replace(propsTitle.charAt(0), propsTitle.charAt(0).toUpperCase());
    return title;

});

</script>

<template>
    <main>
        <SidebarComments v-if="props.isLogin" v-model:sideComment="sideComment" :comments="props.post.comments"
            :user="props.user" @updateComments="updateComments" @addComments="addComments"
            :totalComments="props.totalComments" :idPost="props.post.id" />

        <Head>
            <title>{{ titleHead }}</title>
        </Head>
        <div @click.stop="() => sideComment = false" class="max-w-3xl mx-auto">
            <div class="mb-5">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ props.post.title }}</h1>
                <div class="flex items-center my-4">
                    <div
                        class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.post.user.avatar
                            }}</span>
                    </div>
                    <div>
                        <h4 class="font-semibold">{{ props.post.user.name }}</h4>
                        <p class="text-gray-500 dark:text-gray-400">{{ formatDateTime(props.post.created_at) }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex">
                    <div @click="handleLike"
                        class="flex flex-col items-center justify-center m-3 hover:cursor-pointer w-min">
                        <svg :class="'w-6 h-6 ' + (props.isLiked ? ' text-blue-500' : 'text-gray-800 dark:text-white')"
                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M15.03 9.684h3.965c.322 0 .64.08.925.232.286.153.532.374.717.645a2.109 2.109 0 0 1 .242 1.883l-2.36 7.201c-.288.814-.48 1.355-1.884 1.355-2.072 0-4.276-.677-6.157-1.256-.472-.145-.924-.284-1.348-.404h-.115V9.478a25.485 25.485 0 0 0 4.238-5.514 1.8 1.8 0 0 1 .901-.83 1.74 1.74 0 0 1 1.21-.048c.396.13.736.397.96.757.225.36.32.788.269 1.211l-1.562 4.63ZM4.177 10H7v8a2 2 0 1 1-4 0v-6.823C3 10.527 3.527 10 4.176 10Z"
                                clip-rule="evenodd" />
                        </svg>
                        <p>{{ props.post.like }}</p>
                    </div>
                    <div @click="handleComment"
                        class="flex flex-col items-center justify-center m-3 hover:cursor-pointer w-min">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4 3a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h1v2a1 1 0 0 0 1.707.707L9.414 13H15a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H4Z"
                                clip-rule="evenodd" />
                            <path fill-rule="evenodd"
                                d="M8.023 17.215c.033-.03.066-.062.098-.094L10.243 15H15a3 3 0 0 0 3-3V8h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1v2a1 1 0 0 1-1.707.707L14.586 18H9a1 1 0 0 1-.977-.785Z"
                                clip-rule="evenodd" />
                        </svg>
                        <p>{{ props.totalComments }}</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <svg v-if="props.isSaved" @click="() => deleteSave(props.post.id)"
                        class="text-gray-800 w-7 h-7 hover:cursor-pointer dark:text-white" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M7.833 2c-.507 0-.98.216-1.318.576A1.92 1.92 0 0 0 6 3.89V21a1 1 0 0 0 1.625.78L12 18.28l4.375 3.5A1 1 0 0 0 18 21V3.889c0-.481-.178-.954-.515-1.313A1.808 1.808 0 0 0 16.167 2H7.833Z" />
                    </svg>
                    <svg v-else @click="() => save(props.post.id)"
                        class="text-gray-800 w-7 h-7 hover:cursor-pointer dark:text-white" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m17 21-5-4-5 4V3.889a.92.92 0 0 1 .244-.629.808.808 0 0 1 .59-.26h8.333a.81.81 0 0 1 .589.26.92.92 0 0 1 .244.63V21Z" />
                    </svg>
                    <svg @click="copyLinkPost" class="text-gray-800 w-7 h-7 dark:text-white hover:cursor-pointer"
                        aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.213 9.787a3.391 3.391 0 0 0-4.795 0l-3.425 3.426a3.39 3.39 0 0 0 4.795 4.794l.321-.304m-.321-4.49a3.39 3.39 0 0 0 4.795 0l3.424-3.426a3.39 3.39 0 0 0-4.794-4.795l-1.028.961" />
                    </svg>
                </div>
            </div>
            <img class="object-contain w-full rounded-lg h-96" :src="`/storage/${props.post.image_thumbnail}`"
                :alt="props.post.title" />
            <div class="mt-5 text-lg text-gray-700 dark:text-gray-300">
                <article v-html="props.post.content" class="prose"></article>
            </div>
        </div>
    </main>
</template>
