<script setup lang="ts">
import { CircleHelp } from '@lucide/vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

defineProps<{
    text: string;
}>();
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <button type="button" class="inline-flex text-muted-foreground transition-colors hover:text-foreground" aria-label="Ayuda">
                <CircleHelp class="size-4" />
            </button>
        </TooltipTrigger>
        <TooltipContent class="max-w-xs text-left">{{ text }}</TooltipContent>
    </Tooltip>
</template>
