<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CardBlog from '../Components/CardBlog.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { showToast } from '../helper/toast';
import axios from 'axios';

const props = defineProps({
    savedPosts: Array,
    posts: Array,
    totalPosts: Number,
});

const localSavedPosts = ref([...(props.savedPosts || props.posts || [])]);
const totalCount = ref(props.totalPosts || localSavedPosts.value.length);
const isLoadingMore = ref(false);

const savedPostIds = computed(() => localSavedPosts.value.map(item => item.post_id || item.post?.id));
const hasMore = computed(() => localSavedPosts.value.length < totalCount.value);

const handleScroll = async () => {
    const scrollPosition = window.innerHeight + window.scrollY;
    const bottomThreshold = document.documentElement.scrollHeight - 300;

    if (scrollPosition >= bottomThreshold && !isLoadingMore.value && hasMore.value) {
        isLoadingMore.value = true;
        try {
            const res = await axios.get(`/save-posts/on-scroll?skip=${localSavedPosts.value.length}`);
            if (res.data.posts && res.data.posts.length > 0) {
                localSavedPosts.value.push(...res.data.posts);
            }
        } catch (err) {
            console.error('Failed to load more saved posts:', err);
        } finally {
            isLoadingMore.value = false;
        }
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const deleteSave = async (postId) => {
    try {
        await axios.delete('/save-post/' + postId);
        localSavedPosts.value = localSavedPosts.value.filter(item => (item.post_id || item.post?.id) !== postId);
        totalCount.value = Math.max(0, totalCount.value - 1);
        showToast('Article removed from your bookmarks.', 'success');
    } catch (error) {
        if (error.response?.status === 401) {
            router.visit('/login');
        } else {
            showToast('Failed to remove bookmark.', 'error');
        }
    }
};
</script>

<template>
    <Head>
        <title>Saved Bookmarks — EBlog</title>
        <meta name="description" content="Access your personalized collection of saved engineering articles and architecture deep dives on E-Blog." />
        <meta name="robots" content="noindex, follow" />
    </Head>

    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/80 dark:border-slate-800/80">
            <div>
                <h1 class="text-3xl font-bold font-display text-slate-900 dark:text-white">
                    Saved Articles
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ totalCount }} {{ totalCount === 1 ? 'article' : 'articles' }} in your reading list
                </p>
            </div>

            <Link
                href="/"
                class="self-start sm:self-center inline-flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:text-amber-500 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Explore more articles
            </Link>
        </div>

        <!-- Empty State -->
        <div v-if="localSavedPosts.length === 0" class="py-20 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </div>
            <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">No Bookmarks Saved Yet</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">
                Articles you bookmark while browsing will be saved here for easy reading later.
            </p>
            <div class="mt-6">
                <Link
                    href="/"
                    class="px-6 py-2.5 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-xl transition-all shadow-glow font-display inline-block"
                >
                    Browse Feed
                </Link>
            </div>
        </div>

        <!-- Grid of Saved Articles -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <CardBlog
                v-for="item in localSavedPosts"
                :key="item.id || item.post_id"
                :post="item.post || item"
                :savedIds="savedPostIds"
                @deleteSave="deleteSave"
            />
        </div>

        <!-- Scroll Loader -->
        <div v-if="isLoadingMore" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
            <div v-for="n in 3" :key="n" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4 animate-shimmer">
                <div class="aspect-[16/10] bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
                <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div>
            </div>
        </div>
    </div>
</template>
