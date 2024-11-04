<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmptyLayout from '../Layouts/EmptyLayout.vue';
defineOptions({
    layout: EmptyLayout,
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirm: '',
    invalid: null,
    sameEmail: null,
});

</script>

<template>

    <Head>
        <title>Register</title>
    </Head>
    <form @submit.prevent="form.post('/register')" class="max-w-sm mx-auto my-24 space-y-3">
        <div class="mb-5">

            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name</label>
            <input type="text" id="name" v-model="form.name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Your name" required />
            <p class="text-red-500" v-if="form.errors.name">{{ form.errors.email }}</p>
            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
            <input type="email" id="email" v-model="form.email"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="example@example.com" required />
            <p class="text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
            <p class="text-red-500" v-if="form.errors.sameEmail">{{ form.errors.sameEmail }}</p>
        </div>
        <div class="mb-5">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your
                password</label>
            <input type="password" id="password" v-model="form.password"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                required />
            <p class="text-red-500" v-if="form.errors.password">{{ form.errors.password }}</p>
        </div>
        <div class="mb-5">
            <label for="password_confirm" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm
                password</label>
            <input type="password" id="password_confirm" v-model="form.password_confirm"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                required />
            <p class="text-red-500" v-if="form.errors.password_confirm">{{ form.errors.password_confirm }}</p>
        </div>
        <p class="text-red-500" v-if="form.errors.invalid">{{ form.errors.invalid }}</p>
        <p class="mb-5">Already have an account?
            <Link href="/login" class="text-blue-500 hover:underline">Login here</Link>
        </p>
        <button type="submit"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
    </form>
</template>