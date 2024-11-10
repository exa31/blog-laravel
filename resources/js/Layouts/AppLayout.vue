<script setup>
import { Link, usePage, router, useForm } from '@inertiajs/vue3'
import { computed, onMounted, onUpdated, ref } from 'vue';
import Footer from '../Components/Footer.vue';
import ModalSearch from '../Components/ModalSearch.vue';
import { set } from 'date-fns';

const props = usePage().props;
const isLogin = ref(props.isLogin);
const openSearch = ref(false);
const lastSearch = ref('');

const logout = () => {
    router.post('/logout');
    window.location.reload();
    isLogin.value = false;
}

const handleClick = () => {
    openSearch.value = true;
}

const submitSearch = (e) => {
    e.preventDefault();
    if (e.target.search.value === lastSearch.value) {
        return;
    }
    lastSearch.value = e.target.search.value;
    const search = e.target.search.value;
    router.visit('/?q=' + search);
}

</script>

<template>
    <main class="min-h-screen">
        <ModalSearch v-model:openSearch="openSearch" @submitSearch="submitSearch" />
        <nav
            class="sticky top-0 z-20 w-full bg-white border-b border-gray-200 dark:bg-gray-900 start-0 dark:border-gray-600">
            <div class="flex flex-wrap items-center justify-between max-w-screen-xl p-4 mx-auto ">
                <Link href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white ">EBlog</span>
                </Link>
                <form @submit.prevent="submitSearch" class="hidden space-x-2 sm:flex">
                    <input name="search" type="text" placeholder="Search"
                        class="px-4 py-2 text-sm font-medium text-center text-gray-700 bg-gray-100 rounded-lg dark:bg-gray-600 dark:text-gray-300 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:focus:ring-blue-800">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>
                <div class="flex items-center space-x-4">
                    <button @click="handleClick"
                        class="px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 sm:hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    <div v-if="isLogin" class="space-x-4">
                        <button type="button" @click="logout"
                            class="px-4 py-2 text-sm font-medium text-center text-white bg-gray-700 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Logout</button>
                        <a href="/dashboard" v-if="props.isLogin"
                            class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.user.avatar }}</span>
                        </a>
                    </div>
                    <div v-else class="flex space-x-6 md:order-2 ">
                        <Link href="/login" type="button"
                            class="px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Login</Link>
                        <Link href="/register" type="button"
                            class="px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Register</Link>
                    </div>
                </div>
            </div>
        </nav>
        <div class="container min-h-screen pt-4 mx-auto">
            <slot />
        </div>
        <Footer />
    </main>
</template>