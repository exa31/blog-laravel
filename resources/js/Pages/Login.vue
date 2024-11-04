<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmptyLayout from '../Layouts/EmptyLayout.vue';

defineOptions({
    layout: EmptyLayout,
})

const form = useForm({
    email: '',
    password: '',
    invalid: false,
});
</script>


<template>

    <Head>
        <title>Login</title>
    </Head>
    <form @submit.prevent="form.post('/login', {
        onError: () => form.reset()
    })" class="max-w-sm mx-auto my-40 ">
        <div class="mb-5">
            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
            <input type="email" name="email" id="email" v-model="form.email"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="name@flowbite.com" required />
            <p class="text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
            <p class="text-red-500" v-if="form.errors.invalid">{{ form.errors.invalid }}</p>

        </div>
        <div class="mb-5">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your
                password</label>
            <input type="password" id="password" name="password" v-model="form.password"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                required />
            <p class="text-red-500" v-if="form.errors.password">{{ form.errors.password }}</p>
            <p class="text-red-500" v-if="form.errors.invalid">{{ form.errors.invalid }}</p>

        </div>
        <p class="mb-5">Don't have an account?
            <Link href="/register" class="text-blue-500 hover:underline">Register
            here</Link>
        </p>
        <button type="submit" :disabled="form.processing"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
        <a href="/auth/google" class="flex justify-center p-4 mt-5 text-center border border-black rounded-lg">
            <svg class="w-6 h-6 text-gray-800 me-6 dark:text-white" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd"
                    d="M12.037 21.998a10.313 10.313 0 0 1-7.168-3.049 9.888 9.888 0 0 1-2.868-7.118 9.947 9.947 0 0 1 3.064-6.949A10.37 10.37 0 0 1 12.212 2h.176a9.935 9.935 0 0 1 6.614 2.564L16.457 6.88a6.187 6.187 0 0 0-4.131-1.566 6.9 6.9 0 0 0-4.794 1.913 6.618 6.618 0 0 0-2.045 4.657 6.608 6.608 0 0 0 1.882 4.723 6.891 6.891 0 0 0 4.725 2.07h.143c1.41.072 2.8-.354 3.917-1.2a5.77 5.77 0 0 0 2.172-3.41l.043-.117H12.22v-3.41h9.678c.075.617.109 1.238.1 1.859-.099 5.741-4.017 9.6-9.746 9.6l-.215-.002Z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-center">Login With Google</p>
        </a>
    </form>
</template>