<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Checkbox } from '@/components/ui/checkbox';
import type { AssetListItem } from '@/types/assets';

withDefaults(
    defineProps<{
        asset: AssetListItem;
        selectable?: boolean;
        selected?: boolean;
    }>(),
    {
        selectable: false,
        selected: false,
    },
);

defineEmits<{
    'toggle-select': [id: number];
}>();
</script>

<template>
    <Link
        :href="`/activos/${asset.public_id}`"
        class="relative block rounded-xl border p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:scale-[0.99]"
        :class="
            selectable && selected
                ? 'border-primary bg-accent/40 ring-1 ring-primary'
                : 'border-border bg-card'
        "
    >
        <button
            v-if="selectable"
            type="button"
            class="absolute top-3 right-3 z-10 rounded-md bg-card/80 p-0.5 backdrop-blur"
            @click.stop.prevent="$emit('toggle-select', asset.id)"
        >
            <Checkbox :model-value="selected" />
        </button>

        <div class="flex items-start justify-between gap-2 pr-8">
            <div class="min-w-0">
                <p class="truncate font-medium text-foreground">
                    {{ asset.name }}
                </p>
                <p class="font-mono text-xs text-muted-foreground">
                    {{ asset.internal_code }}
                </p>
            </div>
            <StatusBadge
                v-if="asset.status"
                :label="asset.status.label"
                :color="asset.status.color"
                class="shrink-0"
            />
        </div>

        <div class="mt-3 space-y-1 text-sm text-muted-foreground">
            <p>{{ asset.company?.name }} · {{ asset.branch?.name }}</p>
            <p v-if="asset.department">{{ asset.department.name }}</p>
        </div>

        <div
            class="mt-3 flex items-center justify-between border-t border-border pt-3"
        >
            <div class="text-sm">
                <p v-if="asset.currentResponsible" class="text-foreground">
                    {{ asset.currentResponsible.full_name }}
                </p>
                <p v-else class="text-muted-foreground italic">
                    Sin responsable
                </p>
            </div>
            <ChevronRight class="size-4 text-muted-foreground" />
        </div>
    </Link>
</template>
