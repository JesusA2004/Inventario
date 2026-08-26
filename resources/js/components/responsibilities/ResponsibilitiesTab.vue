<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download, Eye, FileCheck2 } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';

export type ResponsibilitySummary = {
    id: number;
    public_id: string;
    document_number: string | null;
    status: { value: string; label: string; color: string };
    receiver_name: string;
    grantor_name: string;
    issued_at: string | null;
    has_signed_pdf: boolean;
    has_physical_copy: boolean;
    asset?: { public_id: string; internal_code: string; name: string } | null;
};

withDefaults(
    defineProps<{
        responsibilities: ResponsibilitySummary[];
        context?: 'asset' | 'responsible';
        emptyMessage?: string;
    }>(),
    {
        context: 'asset',
        emptyMessage: 'Todavía no hay responsivas registradas.',
    },
);

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <div
        v-if="responsibilities.length === 0"
        class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
    >
        {{ emptyMessage }}
    </div>

    <div v-else class="space-y-3">
        <div
            v-for="responsibility in responsibilities"
            :key="responsibility.id"
            class="rounded-xl border border-border bg-card p-4"
        >
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-foreground">
                        {{
                            context === 'asset'
                                ? responsibility.receiver_name
                                : (responsibility.asset?.name ?? '—')
                        }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatDate(responsibility.issued_at) }}
                        <span
                            v-if="
                                context === 'responsible' &&
                                responsibility.asset
                            "
                        >
                            · {{ responsibility.asset.internal_code }}</span
                        >
                        <span v-if="responsibility.document_number">
                            · {{ responsibility.document_number }}</span
                        >
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Otorgó: {{ responsibility.grantor_name }}
                    </p>
                </div>
                <StatusBadge
                    :label="responsibility.status.label"
                    :color="responsibility.status.color"
                />
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <Badge
                    v-if="responsibility.has_signed_pdf"
                    variant="outline"
                    class="gap-1 text-emerald-700 dark:text-emerald-400"
                >
                    <FileCheck2 class="size-3" /> Firma digital
                </Badge>
                <Badge
                    v-if="responsibility.has_physical_copy"
                    variant="outline"
                    class="gap-1 text-emerald-700 dark:text-emerald-400"
                >
                    <FileCheck2 class="size-3" /> Copia física recibida
                </Badge>

                <div class="ml-auto flex items-center gap-1">
                    <Link
                        :href="`/responsivas/${responsibility.public_id}`"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Eye class="size-3.5" /> Ver
                    </Link>
                    <a
                        :href="`/responsivas/${responsibility.public_id}/pdf`"
                        target="_blank"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Download class="size-3.5" /> PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
