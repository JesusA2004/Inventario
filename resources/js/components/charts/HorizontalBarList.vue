<script setup lang="ts">
import { computed } from 'vue';

type Datum = { label: string; value: number };

const props = withDefaults(
    defineProps<{
        data: Datum[];
        colorVar?: string;
    }>(),
    {
        colorVar: '--chart-1',
    },
);

const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
</script>

<template>
    <div class="space-y-3">
        <div v-if="data.length === 0" class="py-8 text-center text-sm text-muted-foreground">Sin datos para mostrar.</div>
        <div v-for="item in data" :key="item.label" class="group">
            <div class="mb-1 flex items-center justify-between text-xs">
                <span class="truncate text-muted-foreground">{{ item.label }}</span>
                <span class="ml-2 shrink-0 font-medium text-foreground">{{ item.value }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full transition-all"
                    :style="{ width: `${(item.value / max) * 100}%`, backgroundColor: `var(${colorVar})` }"
                />
            </div>
        </div>
    </div>
</template>
