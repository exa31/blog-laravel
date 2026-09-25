<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToastNotification from '../Components/ToastNotification.vue';
import { showToast } from '../helper/toast';

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            showToast(flash.message, 'success');
        }
        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true }
);

watch(
    () => page.props.errors,
    (errors) => {
        if (errors?.error) {
            showToast(errors.error, 'error');
        } else if (errors?.invalid) {
            showToast(errors.invalid, 'error');
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