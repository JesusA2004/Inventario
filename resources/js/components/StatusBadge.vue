<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { statusColorClass } from '@/lib/statusColors';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        label: string;
        color?: string | null;
        class?: string;
    }>(),
    {
        color: 'gray',
    },
);
</script>

<template>
    <Badge
        variant="outline"
        :class="cn('border font-medium', statusColorClass(color), $props.class)"
    >
        {{ label }}
    </Badge>
</template>
