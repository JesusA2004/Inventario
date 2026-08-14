<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ImageOff } from '@lucide/vue';
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
        class="group relative flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg active:scale-[0.99]"
        :class="selectable && selected ? 'border-primary ring-1 ring-primary' : 'border-border'"
    >
        <button
            v-if="selectable"
            type="button"
            class="absolute top-2 right-2 z-10 rounded-md bg-card/90 p-0.5 shadow-sm backdrop-blur"
            @click.stop.prevent="$emit('toggle-select', asset.id)"
        >
            <Checkbox :model-value="selected" />
        </button>

        <!-- Miniatura cuadrada: foto real si existe, si no un ícono neutro -->
        <div class="relative aspect-square w-full overflow-hidden bg-muted">
            <img
                v-if="asset.photo_url"
                :src="asset.photo_url"
                :alt="asset.name"
                loading="lazy"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div v-else class="flex size-full flex-col items-center justify-center gap-1.5 text-muted-foreground/60">
                <ImageOff class="size-8" />
                <span class="text-[11px]">Sin foto</span>
            </div>
            <StatusBadge
                v-if="asset.status"
                :label="asset.status.label"
                :color="asset.status.color"
                class="absolute bottom-2 left-2 shadow-sm"
            />
        </div>

        <div class="flex flex-1 flex-col gap-2 p-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-foreground">
                    {{ asset.name }}
                </p>
                <p class="font-mono text-xs text-muted-foreground">
                    {{ asset.internal_code }}
                </p>
            </div>

            <div class="space-y-0.5 text-xs text-muted-foreground">
                <p class="truncate">{{ asset.company?.name }} · {{ asset.branch?.name }}</p>
                <p v-if="asset.department" class="truncate">{{ asset.department.name }}</p>
            </div>

            <div class="mt-auto border-t border-border pt-2 text-xs">
                <p v-if="asset.currentResponsible" class="truncate text-foreground">
                    {{ asset.currentResponsible.full_name }}
                </p>
                <p v-else class="text-muted-foreground italic">Sin responsable</p>
            </div>
        </div>
    </Link>
</template>
