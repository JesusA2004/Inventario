<script setup lang="ts">
import { computed } from 'vue';

type Point = { month: string; altas: number; bajas: number };

const props = defineProps<{
    data: Point[];
}>();

const width = 600;
const height = 220;
const padding = { top: 16, right: 12, bottom: 28, left: 28 };
const innerWidth = width - padding.left - padding.right;
const innerHeight = height - padding.top - padding.bottom;

const maxValue = computed(() => Math.max(1, ...props.data.flatMap((d) => [d.altas, d.bajas])));

function xFor(index: number): number {
    if (props.data.length <= 1) {
return padding.left;
}

    return padding.left + (index / (props.data.length - 1)) * innerWidth;
}

function yFor(value: number): number {
    return padding.top + innerHeight - (value / maxValue.value) * innerHeight;
}

function pathFor(key: 'altas' | 'bajas'): string {
    return props.data.map((point, index) => `${index === 0 ? 'M' : 'L'} ${xFor(index)} ${yFor(point[key])}`).join(' ');
}

const gridLines = [0, 0.25, 0.5, 0.75, 1];

const labelStep = computed(() => Math.ceil(props.data.length / 6));
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full" style="background-color: var(--chart-1)" /> Altas</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full" style="background-color: var(--chart-5)" /> Bajas</span>
        </div>
        <svg :viewBox="`0 0 ${width} ${height}`" class="w-full" preserveAspectRatio="xMidYMid meet">
            <line
                v-for="fraction in gridLines"
                :key="fraction"
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="padding.top + innerHeight * (1 - fraction)"
                :y2="padding.top + innerHeight * (1 - fraction)"
                stroke="var(--border)"
                stroke-width="1"
            />
            <path :d="pathFor('altas')" fill="none" stroke="var(--chart-1)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <path :d="pathFor('bajas')" fill="none" stroke="var(--chart-5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <g v-for="(point, index) in data" :key="`altas-${index}`">
                <circle :cx="xFor(index)" :cy="yFor(point.altas)" r="2.5" fill="var(--chart-1)">
                    <title>{{ point.month }} · Altas: {{ point.altas }}</title>
                </circle>
                <circle :cx="xFor(index)" :cy="yFor(point.bajas)" r="2.5" fill="var(--chart-5)">
                    <title>{{ point.month }} · Bajas: {{ point.bajas }}</title>
                </circle>
            </g>
            <text
                v-for="(point, index) in data"
                :key="`label-${index}`"
                v-show="index % labelStep === 0"
                :x="xFor(index)"
                :y="height - 6"
                text-anchor="middle"
                font-size="9"
                fill="var(--muted-foreground)"
            >
                {{ point.month }}
            </text>
        </svg>
    </div>
</template>
