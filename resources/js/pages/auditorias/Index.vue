<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, Plus } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated } from '@/types/assets';

type Audit = {
    id: number;
    name: string;
    status: string;
    started_at: string;
    finished_at: string | null;
    company: { name: string } | null;
    branch: { name: string } | null;
    department: { name: string } | null;
    items_count: number;
    found_count: number;
    pending_count: number;
};

const props = defineProps<{
    audits: Paginated<Audit>;
    filters: { status?: string };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Auditorías', href: '/auditorias' }] },
});

const status = ref(props.filters.status ?? 'all');

function applyFilters() {
    router.get('/auditorias', { status: status.value !== 'all' ? status.value : undefined }, { preserveState: true, replace: true });
}

const statusLabels: Record<string, { label: string; color: string }> = {
    en_progreso: { label: 'En progreso', color: 'blue' },
    finalizada: { label: 'Finalizada', color: 'green' },
    cancelada: { label: 'Cancelada', color: 'slate' },
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
}
</script>

<template>
    <Head title="Auditorías" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Auditorías" description="Levantamientos físicos del inventario">
            <template #actions>
                <Link href="/auditorias/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nueva auditoría
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <Select v-model="status" @update:model-value="applyFilters">
            <SelectTrigger class="w-full sm:w-48"><SelectValue placeholder="Estatus" /></SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todas</SelectItem>
                <SelectItem value="en_progreso">En progreso</SelectItem>
                <SelectItem value="finalizada">Finalizada</SelectItem>
                <SelectItem value="cancelada">Cancelada</SelectItem>
            </SelectContent>
        </Select>

        <EmptyState
            v-if="audits.data.length === 0"
            :icon="ClipboardCheck"
            title="Todavía no hay auditorías registradas"
            description="Inicia una auditoría para levantar físicamente el inventario de una sucursal."
        >
            <template #action>
                <Link href="/auditorias/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nueva auditoría
                    </Button>
                </Link>
            </template>
        </EmptyState>

        <template v-else>
            <div class="space-y-3">
                <Link
                    v-for="audit in audits.data"
                    :key="audit.id"
                    :href="`/auditorias/${audit.id}`"
                    class="block rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-foreground">{{ audit.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ audit.company?.name }} · {{ audit.branch?.name }}
                                <span v-if="audit.department"> · {{ audit.department.name }}</span>
                            </p>
                        </div>
                        <StatusBadge :label="statusLabels[audit.status]?.label ?? audit.status" :color="statusLabels[audit.status]?.color ?? 'gray'" />
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-sm text-muted-foreground">
                        <span>{{ audit.found_count }} / {{ audit.items_count }} encontrados</span>
                        <span>Inició {{ formatDate(audit.started_at) }}</span>
                    </div>
                </Link>
            </div>
            <Pager :links="audits.links" />
        </template>
    </div>
</template>
