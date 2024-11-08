<script setup>
import { computed, ref } from 'vue';
import Comment from './Comment.vue';
import HeaderSidebarComments from './HeaderSidebarComments.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps(['comments', 'openComment', 'user', 'commentsReply', 'sideComment', 'idPost', 'totalComments']);
const emits = defineEmits(['update:sideComment', 'addComments', 'updateComments']);

const active = ref(false);

const sideComment = computed({
    get: () => props.sideComment,
    set: (value) => emits('update:sideComment', value)
})

const handleComment = () => {
    active.value = !active.value;
}


const form = useForm({
    content: '',
});

const submitComment = async () => {
    try {
        if (!form.content) {
            return;
        }
        const res = await axios.post('/comments', {
            content: form.content,
            post_id: props.idPost
        });
        if (res.status === 201) {
            const comment = {
                user: {
                    name: props.user.name,
                    avatar: props.user.avatar
                },
                replies: [],
                content: form.content,
                updated_at: new Date()
            }
            emits('addComments', comment);
        }
        form.reset();
    } catch (error) {
        console.log(error);
    }
}


const close = () => {
    sideComment.value = false;
}

const onScroll = async (e) => {
    if ((e.target.clientHeight + e.target.scrollTop) === e.target.scrollHeight) {
        if (props.comments.length === props.totalComments) {
            return;
        }
        const res = await axios.get(`/comments/${props.idPost}/on-scroll?skip=${props.comments.length}`).then(res => {
            res.data.comments.forEach(comment => {
                emits('updateComments', comment);
            });
        }).catch(err => {
            console.log(err);
        });
    }
}

</script>

<template>
    <div @scroll="onScroll"
        :class="'fixed top-0 overflow-y-auto right-0 z-50 flex flex-col h-full min-h-screen bg-slate-100  transition-all duration-200 shadow-2xl ' + (sideComment ? 'w-96' : 'w-0')">
        <HeaderSidebarComments @close="close" :totalComments="props.totalComments" :countComment="comments.length" />
        <div class="mt-4 ">
            <form class="flex flex-col items-center" @submit.prevent="submitComment">
                <div class="flex flex-col p-2 bg-white shadow-2xl">
                    <div
                        :class="'items-center flex overflow-hidden transition-all duration-200 ' + (active ? 'h-12' : 'h-0')">
                        <div
                            class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.user.avatar }}</span>
                        </div>
                        <div class="flex flex-col ml-2">
                            <h1 class="text-gray-800 dark:text-white">{{ user.name }}</h1>
                        </div>
                    </div>
                    <textarea v-model="form.content" @blur="handleComment" @focus="handleComment"
                        class="h-10 p-2 duration-200 border border-gray-300 border-none rounded-lg outline-none focus:h-20 w-80 dark:border-gray-700"
                        placeholder="Write a comment" />
                </div>
                <button :disabled="form.processing"
                    class="p-2 mt-2 text-white bg-blue-500 rounded-lg w-80 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-600">Comment</button>
            </form>
            <Comment v-for="(comment, index) in comments" :key="index" :comment="comment" />
        </div>
    </div>
</template>