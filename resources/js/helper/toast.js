import { ref } from 'vue';

export const toasts = ref([]);

let toastId = 0;

export const showToast = (message, type = 'success', duration = 3000) => {
    const id = ++toastId;
    const toast = { id, message, type };
    toasts.value.push(toast);

    setTimeout(() => {
        removeToast(id);
    }, duration);
};

export const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};
