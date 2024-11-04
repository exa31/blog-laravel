<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue';
import Footer from '../Components/Footer.vue';

const props = usePage().props;
const isLogin = ref(props.isLogin);

const logout = () => {
    router.post('/logout');
    isLogin.value = false;
}

</script>

<template>
    <main>
        <nav
            class="sticky top-0 z-20 w-full bg-white border-b border-gray-200 dark:bg-gray-900 start-0 dark:border-gray-600">
            <div class="flex flex-wrap items-center justify-between max-w-screen-xl p-4 mx-auto ">
                <Link href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white ">EBlog</span>
                </Link>
                <div v-if="isLogin" class="space-x-4">
                    <button type="button" @click="logout"
                        class="px-4 py-2 text-sm font-medium text-center text-white bg-gray-700 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Logout</button>
                    <div
                        class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ props.avatar }}</span>
                    </div>
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
        </nav>
        <div class="container mx-auto">
            <slot />
        </div>
        <Footer />
    </main>
</template>