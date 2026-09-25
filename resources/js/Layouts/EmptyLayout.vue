<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToastNotification from '../Components/ToastNotification.vue';
import { showToast } from '../helper/toast';

const page = usePage();

watch(
    () => [page.props.flash, page.props.errors],
    ([flash, errors]) => {
        const msg = flash?.message;
        const err = flash?.error || errors?.error || errors?.invalid;

        if (msg) {
            showToast(msg, 'success');
        }
        if (err) {
            showToast(err, 'error');
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div>
        <ToastNotification />
        <slot />
    </div>
</template>