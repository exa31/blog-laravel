<script setup>
import { usePage, Head, router } from '@inertiajs/vue3';
import { formatDateTime } from '../helper/index';
import SidebarComments from '../Components/SidebarComments.vue';
import axios from 'axios';
import { ref } from 'vue';

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
console.log(props);
const addComments = (comments) => {
    props.post.comments.unshift(comments);
    props.totalComments++;
}

const updateComments = (comments) => {
    props.post.comments.push(comments);
}


</script>

<template>
    <main>
        <SidebarComments v-if="props.isLogin" v-model:sideComment="sideComment" :comments="props.post.comments"
            :user="props.user" @updateComments="updateComments" @addComments="addComments"
            :totalComments="props.totalComments" :idPost="props.post.id" />

        <Head>
            <title>{{ props.post.slug }}</title>
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
            <div class="flex items-center">
                <div @click="handleLike"
                    class="flex flex-col items-center justify-center m-3 hover:cursor-pointer w-min">
                    <svg :class="'w-6 h-6 ' + (props.isLiked ? ' text-blue-500' : 'text-gray-800 dark:text-white')"
                        aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        viewBox="0 0 24 24">
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
            <img class="object-cover w-full rounded-lg h-96" :src="`/storage/${props.post.image_thumbnail}`"
                :alt="props.post.title" />
            <div class="mt-5 text-lg text-gray-700 dark:text-gray-300">
                <article v-html="props.post.content"></article>
            </div>
        </div>
    </main>
</template>