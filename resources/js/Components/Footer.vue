<script setup>
import { Link } from '@inertiajs/vue3';
import BrandLogo from './BrandLogo.vue';
</script>

<template>
    <footer class="mt-20 border-t border-slate-200 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/40 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex flex-col items-center md:items-start gap-1">
                    <BrandLogo size="sm" />
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Software engineering, system architecture & modern web craft.</p>
                </div>

                <ul class="flex flex-wrap items-center justify-center gap-6 text-sm text-slate-600 dark:text-slate-400">
                    <li>
                        <Link href="/" class="hover:text-amber-500 transition-colors font-medium">Home</Link>
                    </li>
                    <li>
                        <Link href="/save-posts" class="hover:text-amber-500 transition-colors font-medium">Saved Bookmarks</Link>
                    </li>
                    <li>
                        <a href="/dashboard" class="hover:text-amber-500 transition-colors font-medium">Author Panel</a>
                    </li>
                </ul>

                <p class="text-xs text-slate-400 dark:text-slate-500 text-center md:text-right">
                    © {{ new Date().getFullYear() }} EBlog Publication. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>