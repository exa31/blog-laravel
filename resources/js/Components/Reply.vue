<script setup>
import { formatDateTimeComment } from '../helper';

const props = defineProps(['reply']);
</script>

<template>
    <div class="flex justify-center p-8 border-l-4 ">
        <div class="flex flex-col w-full max-w-[320px] leading-1.5">
            <div class="flex text-start rtl:space-x-reverse">
                <div
                    class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.reply.user.avatar }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ props.reply.user.name }}</span>
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{
                        formatDateTimeComment(props.reply.updated_at) }}</span>
                </div>
            </div>
            <p class="py-2 text-sm font-normal text-gray-900 dark:text-white"> {{ props.reply.body }}</p>
        </div>
    </div>
</template>