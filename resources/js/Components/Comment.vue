<script setup>
import { ref } from 'vue';
import Reply from './Reply.vue';
import { formatDateTimeComment } from '../helper';

const props = defineProps(['commentsReply', 'comment']);

const openReply = ref(true);
const formReply = ref(false);

const handleReply = () => {
    openReply.value = !openReply.value;
}

const handleFormReply = () => {
    formReply.value = !formReply.value;
}
</script>

<template>
    <div class="flex flex-col justify-center p-8 ">
        <div class="flex flex-col w-full max-w-[320px] leading-1.5">
            <div class="flex text-start rtl:space-x-reverse">
                <img class="w-8 h-8 rounded-full me-3" src="https://i.pravatar.cc/150?u=1" alt="Jese image">
                <div class="flex flex-col text-start rtl:space-x-reverse">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ comment.user.name }}</span>
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ formatDateTimeComment(
                        comment.updated_at) }}</span>
                </div>
            </div>
            <p class="py-2 text-sm font-normal text-gray-900 dark:text-white"> {{ comment.content }}</p>
            <div
                :class="'flex mb-2 hover:cursor-pointer ' + (comment.replies.length > 0 ? 'justify-between' : 'justify-end')">
                <div v-if="comment.replies.length > 0" @click="handleReply" class="flex space-x-2">
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
                    <p>{{ openReply ? 'Hide Reply' : comments ?? 1 + ' Reply' }} </p>
                </div>
                <button @click="handleFormReply"
                    class="text-sm font-semibold text-blue-500 dark:text-blue-500 hover:underline focus:outline-none">Reply</button>
            </div>
            <div v-if="formReply" class="flex justify-center p-8 border-l-4 ">
                <div class="flex flex-col w-full max-w-[320px] leading-1.5">
                    <form class="flex flex-col items-center">
                        <div class="flex flex-col p-2 bg-white shadow-2xl">
                            <textarea
                                class="w-64 h-20 p-2 duration-200 border-none rounded-lg outline-none active:outline-none active:border-none focus:outline-none focus:border-none focus:h-20 dark:border-gray-700"
                                placeholder="Write a comment" />
                        </div>
                        <div class="flex self-end space-x-2">
                            <button @click="handleFormReply" type="button"
                                class="p-2 mt-2 text-white bg-gray-500 rounded-lg hover:bg-gray-600 dark:bg-gray-500 dark:hover:bg-gray-600">Cancel</button>
                            <button
                                class="p-2 mt-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-600">Reply</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- <Reply v-if="openReply" />
            <Reply v-if="openReply" />
            <Reply v-if="openReply" />
            <Reply v-if="openReply" />
            <Reply v-if="openReply" />
            <Reply v-if="openReply" /> -->
        </div>
    </div>
</template>