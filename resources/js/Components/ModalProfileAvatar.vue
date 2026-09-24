<script setup>
import { ref, reactive, watch, nextTick, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { showToast } from '../helper/toast';
import UserAvatar from './UserAvatar.vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits(['update:modelValue', 'avatarUpdated']);

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || page.props.user || {});

const activeTab = ref('avatar'); // 'avatar' | 'accounts'

const fileInputRef = ref(null);
const imageSrc = ref(null);
const isUploading = ref(false);
const isUnbindingApple = ref(false);
const isDraggingOver = ref(false);

// Canvas & Cropper State
const zoom = ref(1);
const minZoom = ref(1);
const maxZoom = ref(3);
const pan = reactive({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = reactive({ x: 0, y: 0 });
const imageDimensions = reactive({ width: 0, height: 0, naturalWidth: 0, naturalHeight: 0 });

const containerSize = 280; // Size of the circular crop viewport in px

const closeModal = () => {
    if (isUploading.value || isUnbindingApple.value) return;
    emits('update:modelValue', false);
    resetCropper();
};

const resetCropper = () => {
    imageSrc.value = null;
    zoom.value = 1;
    pan.x = 0;
    pan.y = 0;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        loadFile(file);
    }
};

const handleDrop = (e) => {
    isDraggingOver.value = false;
    const file = e.dataTransfer.files?.[0];
    if (file) {
        loadFile(file);
    }
};

const loadFile = (file) => {
    if (!file.type.startsWith('image/')) {
        showToast('Please select a valid image file (PNG, JPG, WebP).', 'error');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showToast('Image size exceeds 5MB limit. Please choose a smaller photo.', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            imageDimensions.naturalWidth = img.naturalWidth;
            imageDimensions.naturalHeight = img.naturalHeight;

            // Pure Contain: Fit image completely inside container preserving authentic aspect ratio
            const aspect = img.naturalWidth / img.naturalHeight;
            if (aspect >= 1) {
                imageDimensions.width = containerSize;
                imageDimensions.height = containerSize / aspect;
            } else {
                imageDimensions.height = containerSize;
                imageDimensions.width = containerSize * aspect;
            }

            pan.x = 0;
            pan.y = 0;
            zoom.value = 1;
            imageSrc.value = e.target.result;
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
};

// Drag repositioning handlers
const onMouseDown = (e) => {
    if (!imageSrc.value) return;
    isDragging.value = true;
    dragStart.x = e.clientX - pan.x;
    dragStart.y = e.clientY - pan.y;
};

const onMouseMove = (e) => {
    if (!isDragging.value) return;
    pan.x = e.clientX - dragStart.x;
    pan.y = e.clientY - dragStart.y;
};

const onMouseUp = () => {
    isDragging.value = false;
};

// Touch handlers for mobile
const onTouchStart = (e) => {
    if (!imageSrc.value || e.touches.length !== 1) return;
    isDragging.value = true;
    dragStart.x = e.touches[0].clientX - pan.x;
    dragStart.y = e.touches[0].clientY - pan.y;
};

const onTouchMove = (e) => {
    if (!isDragging.value || e.touches.length !== 1) return;
    pan.x = e.touches[0].clientX - dragStart.x;
    pan.y = e.touches[0].clientY - dragStart.y;
};

const onTouchEnd = () => {
    isDragging.value = false;
};

// Generate Cropped Image Canvas
const getCroppedBase64 = () => {
    return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const outputSize = 400;
        canvas.width = outputSize;
        canvas.height = outputSize;
        const ctx = canvas.getContext('2d');

        const img = new Image();
        img.onload = () => {
            const factor = outputSize / containerSize;

            const renderWidth = imageDimensions.width * factor * zoom.value;
            const renderHeight = imageDimensions.height * factor * zoom.value;
            const renderX = (outputSize / 2) - (renderWidth / 2) + (pan.x * factor);
            const renderY = (outputSize / 2) - (renderHeight / 2) + (pan.y * factor);

            ctx.drawImage(img, renderX, renderY, renderWidth, renderHeight);
            let base64 = canvas.toDataURL('image/webp', 0.90);
            if (!base64.startsWith('data:image/webp')) {
                base64 = canvas.toDataURL('image/png', 0.90);
            }
            resolve(base64);
        };
        img.onerror = reject;
        img.src = imageSrc.value;
    });
};

const saveAvatar = async () => {
    if (!imageSrc.value || isUploading.value) return;
    isUploading.value = true;

    try {
        const base64Data = await getCroppedBase64();
        const res = await axios.post('/profile/avatar', {
            avatar_base64: base64Data,
        });

        if (res.data?.user) {
            if (page.props.auth?.user) {
                page.props.auth.user.avatar = res.data.user.avatar;
                page.props.auth.user.avatar_url = res.data.user.avatar_url;
            }
            if (page.props.user) {
                page.props.user.avatar = res.data.user.avatar;
                page.props.user.avatar_url = res.data.user.avatar_url;
            }
            emits('avatarUpdated', res.data.user);
            showToast('Profile picture updated successfully!', 'success');
            
            emits('update:modelValue', false);
            resetCropper();

            router.reload({ preserveScroll: true });
        }
    } catch (err) {
        showToast(err.response?.data?.message || 'Failed to upload profile picture.', 'error');
    } finally {
        isUploading.value = false;
    }
};

const removeAvatar = async () => {
    if (isUploading.value) return;
    isUploading.value = true;

    try {
        const res = await axios.delete('/profile/avatar');
        if (page.props.auth?.user) {
            page.props.auth.user.avatar = null;
            page.props.auth.user.avatar_url = null;
        }
        if (page.props.user) {
            page.props.user.avatar = null;
            page.props.user.avatar_url = null;
        }
        emits('avatarUpdated', null);
        showToast('Profile picture removed.', 'success');
        
        emits('update:modelValue', false);
        resetCropper();

        router.reload({ preserveScroll: true });
    } catch (err) {
        showToast('Failed to remove profile picture.', 'error');
    } finally {
        isUploading.value = false;
    }
};

const unbindApple = async () => {
    if (isUnbindingApple.value) return;

    if (!currentUser.value?.can_unbind_apple) {
        showToast('Cannot disconnect Apple ID. Your account must have a verified primary email or Google account connected.', 'error');
        return;
    }

    if (!confirm('Are you sure you want to disconnect your Apple ID from this account?')) {
        return;
    }

    isUnbindingApple.value = true;
    try {
        const res = await axios.post('/auth/apple/unbind');
        showToast(res.data?.message || 'Apple ID disconnected successfully.', 'success');
        router.reload({ preserveScroll: true });
    } catch (err) {
        showToast(err.response?.data?.message || 'Failed to disconnect Apple ID.', 'error');
    } finally {
        isUnbindingApple.value = false;
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="props.modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
            @click.self="closeModal"
            @mouseup="onMouseUp"
            @touchend="onTouchEnd"
        >
            <div
                class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden animate-scale-up"
            >
                <!-- Modal Header & Tab Navigation -->
                <div class="px-6 pt-5 pb-0 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-4">
                        <div>
                            <h3 class="text-lg font-bold font-display text-slate-900 dark:text-white">
                                Account Settings
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Manage your profile avatar and connected authentication providers.
                            </p>
                        </div>
                        <button
                            @click="closeModal"
                            type="button"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Nav Tabs -->
                    <div class="flex items-center gap-6 text-sm font-medium">
                        <button
                            type="button"
                            @click="activeTab = 'avatar'"
                            :class="[
                                'pb-3 border-b-2 transition-all flex items-center gap-2',
                                activeTab === 'avatar'
                                    ? 'border-amber-500 text-amber-500 font-semibold'
                                    : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Profile Photo
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'accounts'"
                            :class="[
                                'pb-3 border-b-2 transition-all flex items-center gap-2',
                                activeTab === 'accounts'
                                    ? 'border-amber-500 text-amber-500 font-semibold'
                                    : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Connected Accounts
                        </button>
                    </div>
                </div>

                <!-- Modal Body: Tab 1 (Avatar Cropper) -->
                <div v-if="activeTab === 'avatar'" class="p-6 space-y-6">
                    
                    <!-- View 1: Image Dropzone (When No Image Selected) -->
                    <div
                        v-if="!imageSrc"
                        @dragover.prevent="isDraggingOver = true"
                        @dragleave.prevent="isDraggingOver = false"
                        @drop.prevent="handleDrop"
                        :class="[
                            'flex flex-col items-center justify-center p-8 border-2 border-dashed rounded-2xl transition-all text-center cursor-pointer',
                            isDraggingOver
                                ? 'border-amber-500 bg-amber-500/10'
                                : 'border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 hover:border-amber-500/50 dark:hover:border-amber-500/50'
                        ]"
                        @click="() => fileInputRef?.click()"
                    >
                        <div class="mb-4">
                            <UserAvatar :user="currentUser" size="2xl" />
                        </div>

                        <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            Click to upload or drag and drop
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            PNG, JPG or WebP (max. 5MB)
                        </p>

                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                    </div>

                    <!-- View 2: Interactive Circular Cropper -->
                    <div v-else class="space-y-4">
                        <div class="flex items-center justify-center">
                            <div
                                class="relative rounded-full overflow-hidden shadow-2xl border-4 border-amber-500 cursor-grab active:cursor-grabbing select-none touch-none bg-slate-950"
                                :style="{ width: containerSize + 'px', height: containerSize + 'px' }"
                                @mousedown="onMouseDown"
                                @mousemove="onMouseMove"
                                @mouseup="onMouseUp"
                                @mouseleave="onMouseUp"
                                @touchstart="onTouchStart"
                                @touchmove="onTouchMove"
                                @touchend="onTouchEnd"
                            >
                                <img
                                    :src="imageSrc"
                                    alt="Crop target"
                                    class="absolute pointer-events-none origin-center select-none"
                                    :style="{
                                        width: imageDimensions.width + 'px',
                                        height: imageDimensions.height + 'px',
                                        left: '50%',
                                        top: '50%',
                                        transform: `translate(calc(-50% + ${pan.x}px), calc(-50% + ${pan.y}px)) scale(${zoom})`,
                                    }"
                                    draggable="false"
                                />
                                <div class="absolute inset-0 border border-white/20 rounded-full pointer-events-none"></div>
                            </div>
                        </div>

                        <p class="text-center text-xs text-slate-400 flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                            </svg>
                            Drag to reposition photo
                        </p>

                        <!-- Zoom Slider Controls -->
                        <div class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                    Scale & Zoom
                                </span>
                                <span>{{ Math.round(zoom * 100) }}%</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="zoom = Math.max(minZoom, zoom - 0.2)"
                                    class="w-7 h-7 rounded-lg bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs shadow-sm hover:bg-slate-100"
                                >
                                    -
                                </button>
                                <input
                                    type="range"
                                    v-model.number="zoom"
                                    :min="minZoom"
                                    :max="maxZoom"
                                    step="0.05"
                                    class="w-full accent-amber-500 cursor-pointer"
                                />
                                <button
                                    type="button"
                                    @click="zoom = Math.min(maxZoom, zoom + 0.2)"
                                    class="w-7 h-7 rounded-lg bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs shadow-sm hover:bg-slate-100"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Swap File Button -->
                        <div class="flex justify-center">
                            <button
                                type="button"
                                @click="resetCropper"
                                class="text-xs text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 font-semibold"
                            >
                                Choose another photo
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Modal Body: Tab 2 (Connected Accounts) -->
                <div v-else-if="activeTab === 'accounts'" class="p-6 space-y-5">
                    
                    <!-- Account Primary Email Info -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-1">
                        <p class="text-xs font-medium text-slate-400">Account Primary Email</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                            {{ currentUser?.email }}
                        </p>
                    </div>

                    <!-- Provider 1: Google Account -->
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.6h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.9z"/>
                                    <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15.2s.7 5.5 1.9 7.9l3.7-2.9z"/>
                                    <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16.5C3.7 20.2 7.5 23.5 12 23.5z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Google</h4>
                                    <span v-if="currentUser?.has_google_auth" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Connected & Locked
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ currentUser?.has_google_auth ? 'Primary verified identity (Cannot be unbound).' : 'Sign in with Google to update & secure your primary email.' }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <a
                                v-if="!currentUser?.has_google_auth"
                                href="/auth/google"
                                class="px-3.5 py-1.5 text-xs font-semibold text-slate-900 bg-amber-500 hover:bg-amber-400 rounded-xl transition-all font-display"
                            >
                                Connect
                            </a>
                            <div v-else class="flex items-center gap-1 text-slate-400 text-xs">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span class="text-[11px]">Bound</span>
                            </div>
                        </div>
                    </div>

                    <!-- Provider 2: Apple ID -->
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 fill-current text-slate-900 dark:text-white" viewBox="0 0 170 170">
                                    <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.57-7.71-11.62-14.01-6.19-9.57-11.06-20.2-14.61-31.9-3.55-11.69-5.33-22.95-5.33-33.78 0-14.28 3.59-26.17 10.77-35.66 7.18-9.48 16.14-14.29 26.89-14.42 5.11 0 10.75 1.41 16.92 4.22 6.17 2.82 10.15 4.27 11.94 4.35 1.57 0 5.75-1.55 12.55-4.66 6.8-3.09 12.73-4.44 17.78-4.04 13.79.98 24.36 5.86 31.7 14.65-12.19 7.39-18.18 17.52-17.97 30.39.22 10.23 4.1 18.77 11.65 25.61 7.55 6.84 16.63 10.7 27.24 11.58-2.61 7.84-5.99 15.68-10.14 23.52zM119.22 33.15c0-7.39 2.64-14.34 7.92-20.85 5.28-6.51 11.83-10.77 19.65-12.8 1.09 7.18-.76 14.23-5.56 21.15-4.79 6.92-11.23 11.02-19.32 12.3-.54-.6-.94-1.22-1.2-1.85-.99-2.61-1.49-5.26-1.49-7.95z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Apple ID</h4>
                                    <span v-if="currentUser?.has_apple_auth" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Connected
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ currentUser?.has_apple_auth ? (currentUser?.can_unbind_apple ? 'Connected. You can disconnect your Apple ID anytime.' : 'Connected. Cannot disconnect until a verified primary email or Google account is linked.') : 'Link your Apple ID for fast single sign-on.' }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <a
                                v-if="!currentUser?.has_apple_auth"
                                href="/auth/apple"
                                class="px-3.5 py-1.5 text-xs font-semibold text-slate-900 bg-amber-500 hover:bg-amber-400 rounded-xl transition-all font-display"
                            >
                                Connect
                            </a>
                            <div v-else>
                                <button
                                    v-if="currentUser?.can_unbind_apple"
                                    type="button"
                                    @click="unbindApple"
                                    :disabled="isUnbindingApple"
                                    class="px-3.5 py-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-xl transition-all disabled:opacity-50"
                                >
                                    {{ isUnbindingApple ? 'Disconnecting...' : 'Disconnect' }}
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    disabled
                                    title="Verify a primary email or connect Google first before disconnecting Apple ID"
                                    class="px-3.5 py-1.5 text-xs font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-xl cursor-not-allowed opacity-60"
                                >
                                    Locked
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer Actions -->
                <div class="flex items-center justify-between px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <button
                            v-if="activeTab === 'avatar' && currentUser?.avatar && !imageSrc"
                            type="button"
                            @click="removeAvatar"
                            :disabled="isUploading"
                            class="text-xs font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 disabled:opacity-50"
                        >
                            Remove Photo
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            :disabled="isUploading || isUnbindingApple"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 rounded-xl transition-colors disabled:opacity-50"
                        >
                            {{ activeTab === 'accounts' ? 'Close' : 'Cancel' }}
                        </button>
                        <button
                            v-if="activeTab === 'avatar' && imageSrc"
                            type="button"
                            @click="saveAvatar"
                            :disabled="isUploading"
                            class="px-5 py-2 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 active:scale-95 disabled:opacity-50 rounded-xl transition-all shadow-glow font-display flex items-center gap-2"
                        >
                            <svg v-if="isUploading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isUploading ? 'Saving...' : 'Save Avatar' }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </Transition>
</template>
