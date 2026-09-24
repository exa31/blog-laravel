<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Reply from './Reply.vue';
import UserAvatar from './UserAvatar.vue';
import { formatDateTimeComment } from '../helper';
import { showToast } from '../helper/toast';
import axios from 'axios';

const props = defineProps(['comment']);
const page = usePage();

const openReply = ref(false);
const formReply = ref(false);
const replyBody = ref('');
const isSubmitting = ref(false);

const repliesList = computed(() => props.comment.replies || []);

const toggleReplies = () => {
    openReply.value = !openReply.value;
};

const toggleFormReply = () => {
    formReply.value = !formReply.value;
    if (formReply.value) {
        openReply.value = true;
    }
};

const submitReply = async () => {
    if (!replyBody.value.trim() || isSubmitting.value) return;

    isSubmitting.value = true;
    try {
        const res = await axios.post('/comments/replies', {
            body: replyBody.value.trim(),
            comment_id: props.comment.id,
        });

        if (res.status === 201) {
            const user = page.props.auth?.user || page.props.user;
            const newReply = res.data.reply || {
                user: {
                    name: user?.name || 'You',
                    avatar: user?.avatar || 'U',
                },
                body: replyBody.value.trim(),
                created_at: new Date().toISOString(),
            };

            if (!props.comment.replies) {
                props.comment.replies = [];
            }
            props.comment.replies.push(newReply);
            replyBody.value = '';
            formReply.value = false;
            openReply.value = true;
            showToast('Reply added successfully!', 'success');
        }
    } catch (error) {
        showToast('Failed to post reply. Please try again.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800/80 space-y-3">
        <!-- Comment Author Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <UserAvatar :user="props.comment.user" size="sm" />
                <div>
                    <h4 class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white">
                        {{ props.comment.user?.name || 'Reader' }}
                    </h4>
                    <span class="text-[11px] text-slate-400">
                        {{ formatDateTimeComment(props.comment.created_at || props.comment.updated_at) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Comment Content -->
        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed pl-11">
            {{ props.comment.content }}
        </p>

        <!-- Actions Bar -->
        <div class="pl-11 flex items-center gap-4 text-xs font-medium">
            <button
                v-if="repliesList.length > 0"
                @click="toggleReplies"
                class="text-slate-500 hover:text-amber-500 transition-colors flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>{{ openReply ? 'Hide Replies' : `${repliesList.length} ${repliesList.length === 1 ? 'Reply' : 'Replies'}` }}</span>
            </button>

            <button
                @click="toggleFormReply"
                class="text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
            >
                {{ formReply ? 'Cancel Reply' : 'Reply' }}
            </button>
        </div>

        <!-- Inline Reply Form -->
        <div v-if="formReply" class="pl-11 pt-2">
            <form @submit.prevent="submitReply" class="space-y-2">
                <textarea
                    v-model="replyBody"
                    rows="2"
                    placeholder="Write your response..."
                    class="w-full text-xs p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none"
                    required
                ></textarea>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="formReply = false"
                        class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting || !replyBody.trim()"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 rounded-lg transition-all"
                    >
                        {{ isSubmitting ? 'Posting...' : 'Reply' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Threaded Replies -->
        <div v-if="openReply && repliesList.length > 0" class="space-y-1 pt-1">
            <Reply v-for="(reply, idx) in repliesList" :key="idx" :reply="reply" />
        </div>
    </div>
</template>