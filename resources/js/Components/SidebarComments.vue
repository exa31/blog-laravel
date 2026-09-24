<script setup>
import { computed, ref } from 'vue';
import Comment from './Comment.vue';
import HeaderSidebarComments from './HeaderSidebarComments.vue';
import UserAvatar from './UserAvatar.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { showToast } from '../helper/toast';
import axios from 'axios';

const props = defineProps([
    'comments',
    'sideComment',
    'idPost',
    'totalComments',
]);
const emits = defineEmits(['update:sideComment', 'addComments', 'updateComments']);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || page.props.user);
const isAuthenticated = computed(() => !!currentUser.value);

const commentContent = ref('');
const isSubmitting = ref(false);
const isLoadingMore = ref(false);

const close = () => {
    emits('update:sideComment', false);
};

const submitComment = async () => {
    if (!isAuthenticated.value) {
        showToast('Please log in to leave a comment.', 'error');
        router.visit('/login');
        return;
    }

    if (!commentContent.value.trim() || isSubmitting.value) return;

    isSubmitting.value = true;
    try {
        const res = await axios.post('/comments', {
            content: commentContent.value.trim(),
            post_id: props.idPost,
        });

        if (res.status === 201) {
            const newComment = res.data.comment || {
                id: res.data.comment?.id,
                user: {
                    name: currentUser.value.name,
                    avatar: currentUser.value.avatar,
                },
                replies: [],
                content: commentContent.value.trim(),
                created_at: new Date().toISOString(),
            };

            emits('addComments', newComment);
            commentContent.value = '';
            showToast('Comment posted successfully!', 'success');
        }
    } catch (error) {
        showToast('Failed to post comment. Please try again.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const onScroll = async (e) => {
    if (isLoadingMore.value || !props.comments || props.comments.length >= props.totalComments) {
        return;
    }

    const { clientHeight, scrollTop, scrollHeight } = e.target;
    if (clientHeight + scrollTop >= scrollHeight - 50) {
        isLoadingMore.value = true;
        try {
            const res = await axios.get(`/comments/${props.idPost}/on-scroll?skip=${props.comments.length}`);
            if (res.data.comments && res.data.comments.length > 0) {
                res.data.comments.forEach(comment => {
                    emits('updateComments', comment);
                });
            }
        } catch (err) {
            console.error('Failed to load more comments:', err);
        } finally {
            isLoadingMore.value = false;
        }
    }
};
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="props.sideComment"
                class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm"
                @click="close"
            ></div>
        </Transition>

        <!-- Slide-over Drawer -->
        <Transition
            enter-active-class="transform transition duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="props.sideComment"
                class="fixed top-0 right-0 z-50 flex flex-col h-full w-full max-w-md bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden"
            >
                <!-- Drawer Header -->
                <HeaderSidebarComments
                    @close="close"
                    :totalComments="props.totalComments"
                    :countComment="props.comments?.length || 0"
                />

                <!-- Scrollable Content Area -->
                <div @scroll="onScroll" class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/80">
                    
                    <!-- Comment Input Card -->
                    <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50">
                        <div v-if="isAuthenticated" class="space-y-3">
                            <div class="flex items-center gap-2.5">
                                <UserAvatar :user="currentUser" size="xs" />
                                <span class="text-xs font-semibold text-slate-900 dark:text-white">{{ currentUser.name }}</span>
                            </div>

                            <form @submit.prevent="submitComment" class="space-y-3">
                                <textarea
                                    v-model="commentContent"
                                    rows="3"
                                    placeholder="What are your thoughts on this story?"
                                    class="w-full text-sm p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all resize-none shadow-sm"
                                    required
                                ></textarea>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="isSubmitting || !commentContent.trim()"
                                        class="px-5 py-2 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 rounded-xl transition-all shadow-sm active:scale-95"
                                    >
                                        {{ isSubmitting ? 'Publishing...' : 'Publish Comment' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Guest CTA -->
                        <div v-else class="text-center py-6 px-4 border border-dashed border-slate-300 dark:border-slate-700 rounded-2xl">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                                Join the discussion by signing into your account.
                            </p>
                            <Link
                                href="/login"
                                class="inline-flex px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-xl transition-all"
                            >
                                Log in to Comment
                            </Link>
                        </div>
                    </div>

                    <!-- Comments List -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <div v-if="!props.comments || props.comments.length === 0" class="p-8 text-center text-xs text-slate-400">
                            No responses yet. Be the first to share your thoughts!
                        </div>

                        <Comment
                            v-for="(comment, index) in props.comments"
                            :key="comment.id || index"
                            :comment="comment"
                        />

                        <div v-if="isLoadingMore" class="p-4 text-center text-xs text-slate-400">
                            Loading more responses...
                        </div>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>