import { ref } from 'vue';

export const confirmState = ref({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    type: 'danger', // 'danger' | 'warning' | 'info'
    resolve: null,
});

/**
 * Trigger a custom confirmation dialog.
 * Returns a Promise that resolves to true (confirmed) or false (cancelled).
 *
 * @param {Object} options
 * @param {string} options.title - Dialog title
 * @param {string} options.message - Dialog explanation text
 * @param {string} options.confirmText - Label for confirm button
 * @param {string} options.cancelText - Label for cancel button
 * @param {'danger' | 'warning' | 'info'} options.type - Visual theme
 * @returns {Promise<boolean>}
 */
export const showConfirm = ({
    title = 'Are you sure?',
    message = 'Please confirm if you want to proceed with this action.',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    type = 'danger',
} = {}) => {
    return new Promise((resolve) => {
        confirmState.value = {
            isOpen: true,
            title,
            message,
            confirmText,
            cancelText,
            type,
            resolve,
        };
    });
};

export const handleConfirmResponse = (result) => {
    if (confirmState.value.resolve) {
        confirmState.value.resolve(result);
    }
    confirmState.value.isOpen = false;
    confirmState.value.resolve = null;
};
