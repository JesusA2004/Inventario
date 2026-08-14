<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/assets';

defineProps<{
    links: PaginationLink[];
}>();

function label(raw: string): string {
    return raw
        .replace('&laquo; ', '')
        .replace(' &raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
    >
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground/50"
            >
                {{ label(link.label) }}
            </span>
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                :class="
                    cn(
                        'flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'text-foreground hover:bg-accent hover:text-accent-foreground',
                    )
                "
            >
                {{ label(link.label) }}
            </Link>
        </template>
    </nav>
</template>
