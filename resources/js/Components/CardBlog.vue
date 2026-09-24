<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatShortDate, getImageUrl } from '../helper';
import UserAvatar from './UserAvatar.vue';

const props = defineProps(['post', 'save', 'savedIds']);
const emits = defineEmits(['deleteSave', 'save']);

const isSaved = computed(() => {
    if (props.savedIds && Array.isArray(props.savedIds)) {
        return props.savedIds.includes(props.post.id);
    }
    if (props.save && Array.isArray(props.save)) {
        return props.save.some(item => item.post_id === props.post.id);
    }
    return false;
});

const handleSaveToggle = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isSaved.value) {
        emits('deleteSave', props.post.id);
    } else {
        emits('save', props.post.id);
    }
};

const cleanSummary = computed(() => {
    if (!props.post.content) return '';
    const text = props.post.content.replace(/<[^>]*>?/gm, '');
    return text.length > 130 ? text.substring(0, 130) + '...' : text;
});
</script>

<template>
    <article class="group relative flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 shadow-card dark:shadow-card-dark transition-all duration-200 hover:-translate-y-1 overflow-hidden">
        
        <!-- Thumbnail Container -->
        <Link :href="'/post/' + props.post.slug" class="block relative aspect-[16/10] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
            <img
                :src="getImageUrl(props.post)"
                :alt="props.post.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
            />
            
            <!-- Floating Reading Time Badge -->
            <div class="absolute bottom-3 left-3 px-2.5 py-1 text-[11px] font-medium tracking-wide bg-slate-950/70 text-slate-100 backdrop-blur-md rounded-lg shadow-sm">
                {{ props.post.reading_time || 3 }} min read
            </div>

            <!-- Floating Bookmark Button -->
            <button
                @click="handleSaveToggle"
                type="button"
                :title="isSaved ? 'Remove Bookmark' : 'Save Bookmark'"
                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-950/60 hover:bg-slate-950/80 text-white backdrop-blur-md flex items-center justify-center transition-transform active:scale-90"
            >
                <svg
                    class="w-4 h-4 transition-colors"
                    :class="isSaved ? 'text-amber-400 fill-amber-400' : 'text-white'"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                    />
                </svg>
            </button>
        </Link>

        <!-- Card Body -->
        <div class="flex-1 flex flex-col p-5">
            <!-- Author & Date -->
            <div class="flex items-center justify-between mb-3 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-2">
                    <UserAvatar :user="props.post.user" size="xs" />
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ props.post.user?.name || 'Author' }}</span>
                </div>
                <span>{{ formatShortDate(props.post.created_at) }}</span>
            </div>

            <!-- Title -->
            <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors line-clamp-2 mb-2 leading-snug">
                <Link :href="'/post/' + props.post.slug">
                    {{ props.post.title }}
                </Link>
            </h3>

            <!-- Summary -->
            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-4 leading-relaxed flex-1">
                {{ cleanSummary }}
            </p>

            <!-- Card Footer Metadata -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                        {{ props.post.like ?? props.post.likes_count ?? 0 }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        {{ props.post.comments_count ?? (props.post.comments ? props.post.comments.length : 0) }}
                    </span>
                </div>

                <Link
                    :href="'/post/' + props.post.slug"
                    class="font-medium text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform"
                >
                    Read article
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>

    </article>
</template>
