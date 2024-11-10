<script setup>

const emits = defineEmits(['update:openSearch', 'submitSearch']);
const props = defineProps(['openSearch']);

const submitSearch = (e) => {
    e.preventDefault();
    emits('submitSearch', e);
    emits('update:openSearch', false);
}

const handleClick = () => {
    emits('update:openSearch', false);
}

</script>

<template>
    <div @click.self="handleClick"
        :class="' z-50 justify-center w-full  h-screen space-x-2 bg-black bg-opacity-55 sm:hidden ' + (props.openSearch ? 'fixed' : 'hidden overflow-hidden')">
        <form @submit.prevent="submitSearch" class="fixed z-50 flex justify-center w-full mt-20 space-x-2 sm:hidden">
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
    </div>
</template>