<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Inbox } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
    }>(),
    {
        icon: () => Inbox,
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border py-16 text-center"
    >
        <div
            class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <component :is="icon" class="size-6" />
        </div>
        <div class="space-y-1">
            <p class="text-sm font-medium text-foreground">{{ title }}</p>
            <p
                v-if="description"
                class="max-w-sm text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.action" class="mt-2">
            <slot name="action" />
        </div>
    </div>
</template>
