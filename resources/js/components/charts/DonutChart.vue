<script setup lang="ts">
import { computed } from 'vue';

type Datum = { label: string; value: number; colorVar?: string };

const props = withDefaults(
    defineProps<{
        data: Datum[];
    }>(),
    {},
);

const defaultColors = ['--chart-1', '--chart-2', '--chart-3', '--chart-4', '--chart-5'];

const total = computed(() => props.data.reduce((sum, d) => sum + d.value, 0));

const segments = computed(() => {
    let cumulative = 0;
    const radius = 15.9155;
    const circumference = 2 * Math.PI * radius;

    return props.data.map((item, index) => {
        const fraction = total.value > 0 ? item.value / total.value : 0;
        const length = fraction * circumference;
        const offset = cumulative;
        cumulative += length;

        return {
            ...item,
            colorVar: item.colorVar ?? defaultColors[index % defaultColors.length],
            dashArray: `${length} ${circumference - length}`,
            dashOffset: -offset,
            percent: Math.round(fraction * 100),
        };
    });
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
        <div v-if="total === 0" class="py-8 text-center text-sm text-muted-foreground">Sin datos para mostrar.</div>
        <template v-else>
            <svg viewBox="0 0 40 40" class="size-36 shrink-0 -rotate-90">
                <circle cx="20" cy="20" r="15.9155" fill="none" stroke="var(--muted)" stroke-width="4" />
                <circle
                    v-for="segment in segments"
                    :key="segment.label"
                    cx="20"
                    cy="20"
                    r="15.9155"
                    fill="none"
                    :stroke="`var(${segment.colorVar})`"
                    stroke-width="4"
                    :stroke-dasharray="segment.dashArray"
                    :stroke-dashoffset="segment.dashOffset"
                    stroke-linecap="round"
                >
                    <title>{{ segment.label }}: {{ segment.value }} ({{ segment.percent }}%)</title>
                </circle>
                <text x="20" y="20" text-anchor="middle" dominant-baseline="middle" class="rotate-90" :transform="'rotate(90 20 20)'" font-size="6" fill="var(--foreground)">
                    {{ total }}
                </text>
            </svg>
            <ul class="w-full space-y-1.5 text-sm">
                <li v-for="segment in segments" :key="segment.label" class="flex items-center justify-between gap-2">
                    <span class="flex min-w-0 items-center gap-2">
                        <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: `var(${segment.colorVar})` }" />
                        <span class="truncate text-muted-foreground">{{ segment.label }}</span>
                    </span>
                    <span class="shrink-0 font-medium text-foreground">{{ segment.value }}</span>
                </li>
            </ul>
        </template>
    </div>
</template>
