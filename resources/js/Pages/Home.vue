<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import CardBlog from '../Components/CardBlog.vue';
import UserAvatar from '../Components/UserAvatar.vue';
import { formatShortDate, getImageUrl } from '../helper';
import { showToast } from '../helper/toast';

const page = usePage();
const props = defineProps({
    posts: Array,
    totalPosts: Number,
    query: String,
    savedPosts: Array,
    savedPostIds: Array,
});

const localPosts = ref([...(props.posts || [])]);
const localSavedPostIds = ref([...(props.savedPostIds || (props.savedPosts || []).map(p => p.post_id))]);
const isLoadingMore = ref(false);
const hasMore = computed(() => localPosts.value.length < props.totalPosts);

const heroPost = computed(() => {
    if (props.query || localPosts.value.length === 0) return null;
    return localPosts.value[0];
});

const gridPosts = computed(() => {
    if (props.query || localPosts.value.length === 0) return localPosts.value;
    return localPosts.value.slice(1);
});

const handleScroll = async () => {
    const scrollPosition = window.innerHeight + window.scrollY;
    const bottomThreshold = document.documentElement.scrollHeight - 300;

    if (scrollPosition >= bottomThreshold && !isLoadingMore.value && hasMore.value) {
        isLoadingMore.value = true;
        try {
            const res = await axios.get(`/posts/on-scroll?skip=${localPosts.value.length}&q=${encodeURIComponent(props.query || '')}`);
            if (res.data.posts && res.data.posts.length > 0) {
                localPosts.value.push(...res.data.posts);
            }
        } catch (err) {
            console.error('Failed to load more posts:', err);
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

const isUserLoggedIn = computed(() => !!(page.props.auth?.user || page.props.user));

const handleSave = async (id) => {
    if (!isUserLoggedIn.value) {
        showToast('Please log in to bookmark articles.', 'error');
        router.visit('/login');
        return;
    }

    try {
        await axios.post('/save-post', { post_id: id });
        if (!localSavedPostIds.value.includes(id)) {
            localSavedPostIds.value.push(id);
        }
        showToast('Article saved to your bookmarks!', 'success');
    } catch (err) {
        if (err.response?.status === 401) {
            router.visit('/login');
        } else {
            showToast('Failed to save article.', 'error');
        }
    }
};

const handleDeleteSave = async (id) => {
    if (!isUserLoggedIn.value) {
        router.visit('/login');
        return;
    }

    try {
        await axios.delete('/save-post/' + id);
        localSavedPostIds.value = localSavedPostIds.value.filter(postId => postId !== id);
        showToast('Removed from bookmarks.', 'success');
    } catch (err) {
        if (err.response?.status === 401) {
            router.visit('/login');
        } else {
            showToast('Failed to remove bookmark.', 'error');
        }
    }
};

const clearSearch = () => {
    router.visit('/');
};

const pageTitle = computed(() => {
    return props.query
        ? `Search: "${props.query}" — EBlog`
        : 'EBlog — Modern Engineering, Architecture & Tech Publication';
});

const pageDescription = computed(() => {
    return props.query
        ? `Browse search results for "${props.query}" on EBlog.`
        : 'Discover in-depth engineering stories, system architecture insights, and software development practices crafted by experts.';
});

const websiteJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'E-Blog',
    url: typeof window !== 'undefined' ? window.location.origin : '',
    description: pageDescription.value,
    potentialAction: {
        '@type': 'SearchAction',
        target: {
            '@type': 'EntryPoint',
            urlTemplate: `${typeof window !== 'undefined' ? window.location.origin : ''}/?q={search_term_string}`,
        },
        'query-input': 'required name=search_term_string',
    },
}));
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="pageDescription" />
        <meta name="robots" content="index, follow" />

        <!-- Open Graph / Social -->
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="pageDescription" />
        <meta property="og:image" content="/favicon.svg" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="pageDescription" />

        <!-- JSON-LD Structured Data -->
        <component :is="'script'" type="application/ld+json">
            {{ JSON.stringify(websiteJsonLd) }}
        </component>
    </Head>

    <div class="space-y-12">
        <!-- Search Header if Query Active -->
        <div v-if="props.query" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-amber-500/10 dark:bg-amber-500/5 border border-amber-500/20 rounded-2xl">
            <div>
                <p class="text-xs uppercase tracking-wider font-semibold text-amber-600 dark:text-amber-400">Search Results</p>
                <h1 class="text-2xl font-bold font-display text-slate-900 dark:text-white mt-1">
                    "{{ props.query }}"
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Found {{ props.totalPosts }} {{ props.totalPosts === 1 ? 'article' : 'articles' }}
                </p>
            </div>
            <button
                @click="clearSearch"
                class="self-start sm:self-center px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl transition-all shadow-sm"
            >
                Clear Search
            </button>
        </div>

        <!-- Hero Spotlight Article (Shown when no search query and posts exist) -->
        <section v-if="heroPost && !props.query" class="relative">
            <div class="group relative grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 shadow-xl dark:shadow-card-dark overflow-hidden p-6 sm:p-8 lg:p-10 transition-all duration-300">
                
                <!-- Left: Content Info -->
                <div class="lg:col-span-7 flex flex-col justify-center space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Featured Article
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatShortDate(heroPost.created_at) }}</span>
                    </div>

                    <h2 class="font-display font-bold text-2xl sm:text-3xl lg:text-4xl text-slate-900 dark:text-white leading-tight group-hover:text-amber-500 transition-colors">
                        <Link :href="'/post/' + heroPost.slug">
                            {{ heroPost.title }}
                        </Link>
                    </h2>

                    <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed line-clamp-3">
                        {{ heroPost.content ? heroPost.content.replace(/<[^>]*>?/gm, '') : '' }}
                    </p>

                    <!-- Author and Action Row -->
                    <div class="pt-4 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="flex items-center gap-3">
                            <UserAvatar :user="heroPost.user" size="md" />
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ heroPost.user?.name || 'Author' }}</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ heroPost.reading_time || 4 }} min read</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                @click="() => localSavedPostIds.includes(heroPost.id) ? handleDeleteSave(heroPost.id) : handleSave(heroPost.id)"
                                type="button"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-all active:scale-95"
                                :title="localSavedPostIds.includes(heroPost.id) ? 'Remove Bookmark' : 'Bookmark'"
                            >
                                <svg
                                    class="w-4 h-4"
                                    :class="localSavedPostIds.includes(heroPost.id) ? 'text-amber-500 fill-amber-500' : 'text-slate-500 dark:text-slate-400'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                            </button>

                            <Link
                                :href="'/post/' + heroPost.slug"
                                class="px-5 py-2.5 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 active:scale-95 rounded-xl transition-all shadow-glow font-display flex items-center gap-2"
                            >
                                Read Story
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Right: Visual Anchor -->
                <div class="lg:col-span-5 relative aspect-[16/10] lg:aspect-square w-full rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800">
                    <img
                        :src="getImageUrl(heroPost)"
                        :alt="heroPost.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                </div>

            </div>
        </section>

        <!-- Main Articles Grid Section -->
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-xl font-bold font-display text-slate-900 dark:text-white">
                        {{ props.query ? 'Search Results' : 'Latest Articles' }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Showing {{ localPosts.length }} of {{ props.totalPosts }} posts
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="localPosts.length === 0" class="py-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                </div>
                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">No Articles Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Try searching for another keyword or check back later for new engineering insights.
                </p>
                <button
                    v-if="props.query"
                    @click="clearSearch"
                    class="mt-4 px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-xl transition-colors"
                >
                    View All Articles
                </button>
            </div>

            <!-- Posts Bento Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <CardBlog
                    v-for="post in gridPosts"
                    :key="post.id"
                    :post="post"
                    :savedIds="localSavedPostIds"
                    @save="handleSave"
                    @deleteSave="handleDeleteSave"
                />
            </div>

            <!-- Infinite Scroll Skeleton Loader -->
            <div v-if="isLoadingMore" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                <div v-for="n in 3" :key="n" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4 animate-shimmer">
                    <div class="aspect-[16/10] bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
                    <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
                </div>
            </div>

            <!-- End of Feed Message -->
            <div v-if="!hasMore && localPosts.length > 0" class="pt-12 text-center text-xs text-slate-400">
                <span>✦ You're all caught up with the latest publications ✦</span>
            </div>
        </section>
    </div>
</template>
