<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { AssetListItem } from '@/types/assets';

defineProps<{
    asset: AssetListItem;
}>();
</script>

<template>
    <Link
        :href="`/activos/${asset.public_id}`"
        class="block rounded-xl border border-border bg-card p-4 shadow-sm transition-shadow hover:shadow-md active:scale-[0.99]"
    >
        <div class="flex items-start justify-between gap-2">
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
