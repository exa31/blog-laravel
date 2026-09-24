<script setup>
import { computed, ref, watch } from 'vue';
import { getImageUrl } from '../helper';

const props = defineProps({
    user: [Object, String],
    name: String,
    size: {
        type: String,
        default: 'md',
    },
});

const imageError = ref(false);

watch(() => props.user, () => {
    imageError.value = false;
}, { deep: true, immediate: true });

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'w-6 h-6 text-[10px]';
        case 'sm':
            return 'w-8 h-8 text-xs';
        case 'lg':
            return 'w-12 h-12 text-base';
        case 'xl':
            return 'w-20 h-20 text-2xl';
        case '2xl':
            return 'w-28 h-28 text-4xl';
        case 'md':
        default:
            return 'w-9 h-9 text-sm';
    }
});

const resolvedName = computed(() => {
    if (typeof props.user === 'object' && props.user) {
        return props.user.name || props.name || 'User';
    }
    return props.name || (typeof props.user === 'string' ? props.user : 'User');
});

const initial = computed(() => {
    const raw = resolvedName.value || 'U';
    return raw.trim().charAt(0).toUpperCase() || 'U';
});

const avatarUrl = computed(() => {
    if (imageError.value) return null;

    let path = null;
    if (typeof props.user === 'object' && props.user) {
        path = props.user.avatar_url || props.user.avatar;
    } else if (typeof props.user === 'string') {
        path = props.user;
    }

    if (!path || path.length <= 2) {
        // If string is just an initial letter (e.g. "E" or "U")
        return null;
    }

    return getImageUrl(path);
});
</script>

<template>
    <div
        :class="[
            'relative rounded-full shrink-0 flex items-center justify-center select-none overflow-hidden transition-transform',
            sizeClasses,
            !avatarUrl ? 'bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-bold shadow-sm' : 'bg-slate-200 dark:bg-slate-800'
        ]"
    >
        <img
            v-if="avatarUrl"
            :src="avatarUrl"
            :alt="resolvedName"
            class="w-full h-full object-cover"
            @error="imageError = true"
            loading="lazy"
        />
        <span v-else class="font-display leading-none">
            {{ initial }}
        </span>
    </div>
</template>
