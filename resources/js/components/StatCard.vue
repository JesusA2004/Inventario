<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        icon?: LucideIcon;
        hint?: string;
        tone?: 'default' | 'positive' | 'warning' | 'destructive';
    }>(),
    {
        tone: 'default',
    },
);

const toneClasses: Record<string, string> = {
    default: 'bg-primary/10 text-primary',
    positive: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    destructive: 'bg-red-500/10 text-red-600 dark:text-red-400',
};
</script>

<template>
    <div
        class="rounded-xl border border-border bg-card p-4 shadow-sm transition-shadow hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">
                    {{ title }}
                </p>
                <p
                    class="text-2xl font-semibold tracking-tight text-card-foreground"
                >
                    {{ value }}
                </p>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="[
                    'flex size-9 shrink-0 items-center justify-center rounded-lg',
                    toneClasses[tone],
                ]"
            >
                <component :is="icon" class="size-5" />
            </div>
        </div>
    </div>
</template>
