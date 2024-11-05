<script setup>
import { computed, ref } from 'vue';
import Comment from './Comment.vue';

const props = defineProps(['comments', 'openComment', 'user', 'commentsReply', 'sideComment']);
const emits = defineEmits(['update:sideComment']);

const active = ref(false);


const sideComment = computed({
    get: () => props.sideComment,
    set: (value) => {
        emits('update:sideComment', value);
    }
})
const handleComment = () => {
    active.value = !active.value;
}


</script>

<template>
    <div
        :class="'fixed top-0 overflow-y-auto right-0 z-50 flex flex-col h-full min-h-screen bg-slate-100  transition-all duration-200 shadow-2xl ' + (sideComment ? 'w-96' : 'w-0')">
        <div class="flex items-center justify-between px-8 mt-4">
            <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white">Comments (0)</h1>
            <svg @click="sideComment = false" class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18 17.94 6M18 18 6.06 6" />
            </svg>
        </div>
        <div class="mt-4 ">
            <form class="flex flex-col items-center">
                <div class="flex flex-col p-2 bg-white shadow-2xl">
                    <div
                        :class="'items-center flex overflow-hidden transition-all duration-200 ' + (active ? 'h-12' : 'h-0')">
                        <img src="https://i.pravatar.cc/150?u=1" alt="avatar" class="w-10 h-10 rounded-full" />
                        <div class="flex flex-col ml-2">
                            <h1 class="text-gray-800 dark:text-white">{{ user.name }}</h1>
                        </div>
                    </div>
                    <input @blur="handleComment" @focus="handleComment"
                        class="h-10 p-2 duration-200 border border-gray-300 border-none rounded-lg outline-none focus:h-20 w-80 dark:border-gray-700"
                        placeholder="Write a comment" />
                </div>
                <button
                    class="p-2 mt-2 text-white bg-blue-500 rounded-lg w-80 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-600">Comment</button>
            </form>
            <Comment />
            <Comment />
        </div>
    </div>
</template>