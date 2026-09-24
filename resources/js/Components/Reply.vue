<script setup>
import { formatDateTimeComment } from '../helper';
import UserAvatar from './UserAvatar.vue';

const props = defineProps(['reply']);
</script>

<template>
    <div class="relative pl-6 py-3 ml-4 border-l-2 border-slate-200 dark:border-slate-800 space-y-2">
        <div class="flex items-center gap-2.5">
            <UserAvatar :user="props.reply.user" size="xs" />
            <div>
                <h5 class="text-xs font-semibold text-slate-900 dark:text-white leading-tight">
                    {{ props.reply.user?.name || 'Anonymous' }}
                </h5>
                <span class="text-[10px] text-slate-400">
                    {{ formatDateTimeComment(props.reply.created_at || props.reply.updated_at) }}
                </span>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed pl-8">
            {{ props.reply.body }}
        </p>
    </div>
</template>