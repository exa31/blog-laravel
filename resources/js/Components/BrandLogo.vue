<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg', 'xl'
    },
    showText: {
        type: Boolean,
        default: true,
    },
    href: {
        type: String,
        default: '/',
    },
    isLink: {
        type: Boolean,
        default: true,
    },
});

const dimensions = computed(() => {
    switch (props.size) {
        case 'sm':
            return {
                iconClass: 'w-8 h-8 rounded-lg',
                svgSize: '24',
                textClass: 'text-xl',
                subtitleClass: 'text-[10px]',
            };
        case 'lg':
            return {
                iconClass: 'w-12 h-12 rounded-2xl',
                svgSize: '36',
                textClass: 'text-3xl',
                subtitleClass: 'text-xs',
            };
        case 'xl':
            return {
                iconClass: 'w-16 h-16 rounded-3xl',
                svgSize: '48',
                textClass: 'text-4xl',
                subtitleClass: 'text-sm',
            };
        case 'md':
        default:
            return {
                iconClass: 'w-10 h-10 rounded-xl',
                svgSize: '30',
                textClass: 'text-2xl',
                subtitleClass: 'text-[11px]',
            };
    }
});
</script>

<template>
    <component
        :is="isLink ? Link : 'div'"
        :href="isLink ? href : undefined"
        class="inline-flex items-center gap-3 group select-none transition-transform duration-200"
        :class="{ 'hover:opacity-95': isLink }"
    >
        <!-- Icon Emblem -->
        <div
            class="relative flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 border border-slate-700/80 shadow-glow group-hover:scale-105 group-hover:border-amber-500/50 transition-all duration-300 overflow-hidden"
            :class="dimensions.iconClass"
        >
            <!-- Ambient Glow Orb -->
            <div class="absolute -inset-1 bg-gradient-to-r from-amber-500/20 to-orange-500/20 blur-md opacity-60 group-hover:opacity-100 transition-opacity"></div>

            <!-- Custom Geometric E-Blog SVG Logo -->
            <svg
                class="relative z-10 drop-shadow-[0_2px_8px_rgba(245,158,11,0.5)]"
                :width="dimensions.svgSize"
                :height="dimensions.svgSize"
                viewBox="0 0 48 48"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <linearGradient id="logo-amber-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#FDE68A" />
                        <stop offset="50%" stop-color="#F59E0B" />
                        <stop offset="100%" stop-color="#D97706" />
                    </linearGradient>
                </defs>

                <!-- Monogram Body -->
                <path
                    d="M12 12C12 9.79 13.79 8 16 8H34C35.65 8 37 9.34 37 11C37 12.65 35.65 14 34 14H18V19H30C31.65 19 33 20.34 33 22C33 23.65 31.65 25 30 25H18V34H35C36.65 34 38 35.34 38 37C38 38.65 36.65 40 35 40H16C13.79 40 12 38.21 12 36V12Z"
                    fill="url(#logo-amber-grad)"
                />
                <!-- Glowing Spark Dot -->
                <circle cx="38" cy="11" r="2.5" fill="#FEF3C7" />
                <!-- Subtle Diamond Accent -->
                <path d="M37 22L40 20L38.5 24L41 23.5L36 30L37.5 25.5L34.5 26L37 22Z" fill="#F59E0B" />
            </svg>
        </div>

        <!-- Typography Brand Name -->
        <div v-if="showText" class="flex flex-col">
            <span
                class="font-display font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight flex items-center"
                :class="dimensions.textClass"
            >
                EBlog<span class="text-amber-500 font-black animate-pulse">.</span>
            </span>
        </div>
    </component>
</template>
