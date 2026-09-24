<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import BrandLogo from '../Components/BrandLogo.vue';
import Footer from '../Components/Footer.vue';
import ModalProfileAvatar from '../Components/ModalProfileAvatar.vue';
import ModalSearch from '../Components/ModalSearch.vue';
import ToastNotification from '../Components/ToastNotification.vue';
import UserAvatar from '../Components/UserAvatar.vue';
import { showToast } from '../helper/toast';

const page = usePage();
const auth = computed(() => page.props.auth || {});
const currentUser = computed(() => auth.value.user || page.props.user);
const isAuthenticated = computed(() => !!currentUser.value);

const openSearch = ref(false);
const showDropdown = ref(false);
const showAvatarModal = ref(false);
const mobileMenuOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
};

const closeDropdown = () => {
    showDropdown.value = false;
};

const openAvatarCropper = () => {
    showDropdown.value = false;
    showAvatarModal.value = true;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

const handleGlobalKeyDown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        openSearch.value = true;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside, true);
    window.addEventListener('keydown', handleGlobalKeyDown);

    if (page.props.flash?.message) {
        showToast(page.props.flash.message, 'success');
    }
    if (page.props.flash?.error) {
        showToast(page.props.flash.error, 'error');
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside, true);
    window.removeEventListener('keydown', handleGlobalKeyDown);
});

const handleSearchSubmit = (query) => {
    router.visit('/?q=' + encodeURIComponent(query));
};

const handleAvatarUpdated = (updatedUser) => {
    if (page.props.auth?.user) {
        page.props.auth.user.avatar = updatedUser?.avatar || null;
        page.props.auth.user.avatar_url = updatedUser?.avatar_url || null;
    }
    if (page.props.user) {
        page.props.user.avatar = updatedUser?.avatar || null;
        page.props.user.avatar_url = updatedUser?.avatar_url || null;
    }
};

const logout = () => {
    router.post('/logout', {}, {
        onSuccess: () => {
            showToast('You have been logged out.', 'success');
        }
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 transition-colors duration-200">
        <ModalSearch v-model:openSearch="openSearch" @submitSearch="handleSearchSubmit" />
        <ModalProfileAvatar v-model="showAvatarModal" @avatarUpdated="handleAvatarUpdated" />
        <ToastNotification />

        <!-- Sticky Glassmorphic Navbar -->
        <header class="sticky top-0 z-40 w-full border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-[#0B0F19]/80 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
                    
                    <!-- Left: Logo & Nav Links -->
                    <div class="flex items-center gap-8">
                        <BrandLogo size="md" />

                        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                            <Link
                                href="/"
                                class="text-slate-600 dark:text-slate-300 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                            >
                                Explore
                            </Link>
                            <Link
                                v-if="isAuthenticated"
                                href="/save-posts"
                                class="text-slate-600 dark:text-slate-300 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                            >
                                Bookmarks
                            </Link>
                        </nav>
                    </div>

                    <!-- Center/Right: Quick Search Trigger Button -->
                    <div class="flex-1 max-w-md hidden sm:block">
                        <button
                            type="button"
                            @click="openSearch = true"
                            class="w-full flex items-center justify-between px-4 py-2 text-sm text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-150"
                        >
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Search articles...</span>
                            </div>
                            <kbd class="inline-flex items-center gap-0.5 px-2 py-0.5 text-[11px] font-mono font-medium text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-sm">
                                <span>⌘</span>K
                            </kbd>
                        </button>
                    </div>

                    <!-- Right Actions: Auth & Profile -->
                    <div class="flex items-center gap-3">
                        <!-- Mobile Search Button -->
                        <button
                            @click="openSearch = true"
                            class="sm:hidden p-2 text-slate-600 dark:text-slate-300 hover:text-amber-500 rounded-lg"
                            aria-label="Search"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <!-- Authenticated User Menu -->
                        <div v-if="isAuthenticated" ref="dropdownRef" class="relative">
                            <button
                                @click="toggleDropdown"
                                type="button"
                                class="flex items-center gap-2.5 p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                            >
                                <UserAvatar :user="currentUser" size="md" />
                                <span class="hidden lg:block text-sm font-medium text-slate-700 dark:text-slate-200 max-w-[120px] truncate">
                                    {{ currentUser.name }}
                                </span>
                                <svg class="w-4 h-4 text-slate-400 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="transform scale-95 opacity-0"
                                enter-to-class="transform scale-100 opacity-100"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="transform scale-100 opacity-100"
                                leave-to-class="transform scale-95 opacity-0"
                            >
                                <div
                                    v-if="showDropdown"
                                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-2 z-50 divide-y divide-slate-100 dark:divide-slate-800/80"
                                >
                                    <div class="px-4 py-3 flex items-center gap-3">
                                        <UserAvatar :user="currentUser" size="lg" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ currentUser.name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ currentUser.email }}</p>
                                        </div>
                                    </div>

                                    <div class="py-1">
                                        <button
                                            type="button"
                                            @click="openAvatarCropper"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-amber-500 transition-colors text-left"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Profile & Security
                                        </button>
                                        <Link
                                            href="/save-posts"
                                            @click="closeDropdown"
                                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-amber-500 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                            </svg>
                                            Saved Articles
                                        </Link>
                                        <a
                                            href="/dashboard"
                                            @click="closeDropdown"
                                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-amber-500 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                            Admin Dashboard
                                        </a>
                                    </div>

                                    <div class="pt-1">
                                        <button
                                            @click="logout"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors text-left"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Sign Out
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Guest Buttons -->
                        <div v-else class="flex items-center gap-3">
                            <Link
                                href="/login"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                class="px-4 py-2 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 active:scale-95 rounded-xl transition-all shadow-sm font-display"
                            >
                                Get Started
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>

        <!-- Footer -->
        <Footer />
    </div>
</template>
