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

const fileInputRef = ref(null);
const imageSrc = ref(null);
const isUploading = ref(false);
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
    if (isUploading.value) return;
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
                // Landscape / wide: fit width to containerSize, calculate height proportionally
                imageDimensions.width = containerSize;
                imageDimensions.height = containerSize / aspect;
            } else {
                // Portrait / tall: fit height to containerSize, calculate width proportionally
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
        const outputSize = 400; // High-res 400x400 output
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
            // Update local inertia page auth state immediately
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
            
            // Auto close modal
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
        
        // Auto close modal
        emits('update:modelValue', false);
        resetCropper();

        router.reload({ preserveScroll: true });
    } catch (err) {
        showToast('Failed to remove profile picture.', 'error');
    } finally {
        isUploading.value = false;
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
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-lg font-bold font-display text-slate-900 dark:text-white">
                            Update Profile Photo
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Crop and position your avatar to personalize your account.
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

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    
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
                            <!-- Circular Crop Viewport -->
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

                                <!-- Crop Overlay Hint -->
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

                <!-- Modal Footer Actions -->
                <div class="flex items-center justify-between px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <button
                            v-if="currentUser?.avatar && !imageSrc"
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
                            :disabled="isUploading"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 rounded-xl transition-colors disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="imageSrc"
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
