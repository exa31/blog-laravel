<script setup>
import { usePage, Head, router, Link } from '@inertiajs/vue3';
import { formatDateTime, getImageUrl } from '../helper/index';
import SidebarComments from '../Components/SidebarComments.vue';
import UserAvatar from '../Components/UserAvatar.vue';
import { showToast } from '../helper/toast';
import axios from 'axios';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    post: Object,
    totalComments: Number,
    isLiked: Boolean,
    isSaved: Boolean,
});

const localIsLiked = ref(props.isLiked || false);
const localLikesCount = ref(props.post?.like ?? props.post?.likes_count ?? 0);
const localIsSaved = ref(props.isSaved || false);
const localTotalComments = ref(props.totalComments || 0);
const localComments = ref([...(props.post?.comments || [])]);

const sideComment = ref(false);
const isLiking = ref(false);

const isUserLoggedIn = computed(() => !!(page.props.auth?.user || page.props.user));

const handleLike = async () => {
    if (!isUserLoggedIn.value) {
        showToast('Please log in to like articles.', 'error');
        router.visit('/login');
        return;
    }

    if (isLiking.value) return;
    isLiking.value = true;

    try {
        const res = await axios.post('/like/' + props.post.id);
        localIsLiked.value = res.data.isLiked;
        localLikesCount.value = res.data.like;
        if (localIsLiked.value) {
            showToast('Added to liked stories!', 'success');
        }
    } catch (err) {
        if (err.response?.status === 401) {
            router.visit('/login');
        } else {
            showToast('Failed to update like.', 'error');
        }
    } finally {
        isLiking.value = false;
    }
};

const handleCommentToggle = () => {
    sideComment.value = !sideComment.value;
};

const handleSaveToggle = async () => {
    if (!isUserLoggedIn.value) {
        showToast('Please log in to bookmark articles.', 'error');
        router.visit('/login');
        return;
    }

    if (localIsSaved.value) {
        try {
            await axios.delete('/save-post/' + props.post.id);
            localIsSaved.value = false;
            showToast('Removed from bookmarks.', 'success');
        } catch (err) {
            showToast('Failed to remove bookmark.', 'error');
        }
    } else {
        try {
            await axios.post('/save-post', { post_id: props.post.id });
            localIsSaved.value = true;
            showToast('Article bookmarked!', 'success');
        } catch (err) {
            showToast('Failed to bookmark article.', 'error');
        }
    }
};

const copyLinkPost = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        showToast('Article link copied to clipboard!', 'success');
    } catch (e) {
        showToast('Failed to copy link.', 'error');
    }
};

const addComments = (comment) => {
    localComments.value.unshift(comment);
    localTotalComments.value++;
};

const updateComments = (comment) => {
    localComments.value.push(comment);
};

const cleanExcerpt = computed(() => {
    const raw = props.post?.content ? props.post.content.replace(/<[^>]*>?/gm, '') : '';
    return raw.slice(0, 160).trim() + (raw.length > 160 ? '...' : '');
});

const postImageUrl = computed(() => {
    return getImageUrl(props.post);
});

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: props.post?.title,
    description: cleanExcerpt.value,
    image: [postImageUrl.value],
    datePublished: props.post?.created_at,
    dateModified: props.post?.updated_at || props.post?.created_at,
    author: {
        '@type': 'Person',
        name: props.post?.user?.name || 'Editorial Author',
    },
    publisher: {
        '@type': 'Organization',
        name: 'E-Blog',
        logo: {
            '@type': 'ImageObject',
            url: '/favicon.svg',
        },
    },
    mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': typeof window !== 'undefined' ? window.location.href : '',
    },
}));
</script>

<template>
    <main class="relative pb-24">
        <Head>
            <title>{{ props.post.title }} — EBlog</title>
            <meta name="description" :content="cleanExcerpt" />
            <meta name="author" :content="props.post.user?.name || 'EBlog Author'" />
            <meta name="robots" content="index, follow" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="props.post.title" />
            <meta property="og:description" :content="cleanExcerpt" />
            <meta property="og:image" :content="postImageUrl" />
            <meta property="article:published_time" :content="props.post.created_at" />
            <meta property="article:author" :content="props.post.user?.name" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="props.post.title" />
            <meta name="twitter:description" :content="cleanExcerpt" />
            <meta name="twitter:image" :content="postImageUrl" />

            <!-- JSON-LD Structured Data -->
            <component :is="'script'" type="application/ld+json">
                {{ JSON.stringify(jsonLd) }}
            </component>
        </Head>

        <!-- Comments Drawer -->
        <SidebarComments
            v-model:sideComment="sideComment"
            :comments="localComments"
            :idPost="props.post.id"
            :totalComments="localTotalComments"
            @addComments="addComments"
            @updateComments="updateComments"
        />

        <div class="max-w-3xl mx-auto">
            
            <!-- Breadcrumbs / Back Link -->
            <div class="mb-8">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-amber-500 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to all articles
                </Link>
            </div>

            <!-- Article Header -->
            <header class="space-y-6 mb-10">
                <div class="flex items-center gap-2 text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-widest">
                    <span>Engineering Insight</span>
                    <span>•</span>
                    <span>{{ props.post.reading_time || 4 }} min read</span>
                </div>

                <h1 class="font-display font-bold text-3xl sm:text-4xl lg:text-5xl text-slate-900 dark:text-white leading-[1.15] tracking-tight">
                    {{ props.post.title }}
                </h1>

                <!-- Author Row -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-200/80 dark:border-slate-800/80">
                    <div class="flex items-center gap-3">
                        <UserAvatar :user="props.post.user" size="lg" />
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ props.post.user?.name || 'Author' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Published on {{ formatDateTime(props.post.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Featured Thumbnail -->
            <div class="mb-12 rounded-3xl overflow-hidden shadow-xl dark:shadow-card-dark bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-800/80 aspect-[16/9]">
                <img
                    :src="postImageUrl"
                    :alt="props.post.title"
                    class="w-full h-full object-cover"
                />
            </div>

            <!-- Article Rich Body -->
            <div class="prose prose-slate dark:prose-invert prose-lg max-w-none prose-headings:font-display prose-headings:tracking-tight prose-a:text-amber-500 prose-img:rounded-2xl prose-pre:bg-slate-900 prose-pre:border prose-pre:border-slate-800">
                <article v-html="props.post.content"></article>
            </div>

            <!-- Article End Divider -->
            <div class="mt-16 pt-8 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                <span>✦ Thank you for reading ✦</span>
            </div>

        </div>

        <!-- Floating Sticky Tactile Reaction Bar -->
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-30 flex items-center gap-2 p-2 rounded-2xl bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-700/80 shadow-2xl">
            
            <!-- Like Button -->
            <button
                @click="handleLike"
                type="button"
                :class="[
                    'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all active:scale-90',
                    localIsLiked
                        ? 'bg-rose-500/15 text-rose-500 dark:bg-rose-500/20'
                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
                title="Like story"
            >
                <svg
                    class="w-4 h-4 transition-transform"
                    :class="localIsLiked ? 'fill-rose-500 text-rose-500 scale-110' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>{{ localLikesCount }}</span>
            </button>

            <!-- Comments Toggle Button -->
            <button
                @click="handleCommentToggle"
                type="button"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all active:scale-90"
                title="View responses"
            >
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>{{ localTotalComments }}</span>
            </button>

            <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- Bookmark Button -->
            <button
                @click="handleSaveToggle"
                type="button"
                class="p-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all active:scale-90"
                :title="localIsSaved ? 'Remove Bookmark' : 'Save Bookmark'"
            >
                <svg
                    class="w-4 h-4"
                    :class="localIsSaved ? 'text-amber-500 fill-amber-500' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>

            <!-- Share Link Button -->
            <button
                @click="copyLinkPost"
                type="button"
                class="p-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all active:scale-90"
                title="Copy story link"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
            </button>

        </div>
    </main>
</template>
