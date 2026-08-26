<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlarmClockOff,
    Eye,
    FileCheck2,
    FileClock,
    FileSignature,
    FileText,
    Send,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Combobox from '@/components/Combobox.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated } from '@/types/assets';

type Responsibility = {
    id: number;
    public_id: string;
    document_number: string | null;
    status: { value: string; label: string; color: string };
    receiver_name: string;
    receiver_email: string | null;
    issued_at: string | null;
    has_signed_pdf: boolean;
    has_physical_copy: boolean;
    asset: { public_id: string; internal_code: string; name: string } | null;
    company: { id: number; name: string } | null;
    responsiblePerson: {
        id: number;
        full_name: string;
        email: string | null;
    } | null;
};

const props = defineProps<{
    responsibilities: Paginated<Responsibility>;
    filters: {
        q?: string;
        company_id?: string;
        status?: string;
        from?: string;
        to?: string;
        signed?: string;
        physical?: string;
    };
    companies: { id: number; name: string }[];
    statusOptions: { value: string; label: string; color: string }[];
    kpis: {
        pending_send: number;
        sent: number;
        viewed: number;
        signed_digitally: number;
        with_physical_copy: number;
        expired: number;
    };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Responsivas', href: '/responsivas' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const signedFilter = ref(props.filters.signed ?? 'all');
const physicalFilter = ref(props.filters.physical ?? 'all');

const companyOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);

function applyFilters() {
    router.get(
        '/responsivas',
        {
            q: search.value || undefined,
            company_id:
                companyFilter.value !== 'all' ? companyFilter.value : undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            from: from.value || undefined,
            to: to.value || undefined,
            signed:
                signedFilter.value !== 'all' ? signedFilter.value : undefined,
            physical:
                physicalFilter.value !== 'all'
                    ? physicalFilter.value
                    : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

function clearFilters() {
    search.value = '';
    companyFilter.value = 'all';
    statusFilter.value = 'all';
    from.value = '';
    to.value = '';
    signedFilter.value = 'all';
    physicalFilter.value = 'all';
    applyFilters();
}

const activeFiltersCount = computed(
    () =>
        [
            companyFilter.value,
            statusFilter.value,
            signedFilter.value,
            physicalFilter.value,
        ].filter((v) => v !== 'all').length +
        (from.value ? 1 : 0) +
        (to.value ? 1 : 0),
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
    <Head title="Responsivas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Responsivas"
            description="Documentos de entrega de equipo, firma electrónica y copias físicas"
        />

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <StatCard
                title="Pendientes de enviar"
                :value="kpis.pending_send"
                :icon="FileText"
            />
            <StatCard title="Enviadas" :value="kpis.sent" :icon="Send" />
            <StatCard
                title="Vistas"
                :value="kpis.viewed"
                :icon="Eye"
                tone="warning"
            />
            <StatCard
                title="Firmadas digitalmente"
                :value="kpis.signed_digitally"
                :icon="FileSignature"
                tone="positive"
            />
            <StatCard
                title="Con copia física"
                :value="kpis.with_physical_copy"
                :icon="FileCheck2"
                tone="positive"
            />
            <StatCard
                title="Vencidas"
                :value="kpis.expired"
                :icon="AlarmClockOff"
                tone="destructive"
            />
        </div>

        <FilterBar
            :search="search"
            search-placeholder="Nombre, correo, clave, serie o ID de responsiva..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Combobox
                v-model="companyFilter"
                :options="companyOptions"
                placeholder="Empresa"
                class="w-full lg:w-44"
                @update:model-value="applyFilters"
            />
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-44"
                    ><SelectValue placeholder="Estatus"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los estatus</SelectItem>
                    <SelectItem
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                        >{{ option.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select v-model="signedFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Firma digital"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Firma digital: todos</SelectItem>
                    <SelectItem value="true">Con firma digital</SelectItem>
                    <SelectItem value="false">Sin firma digital</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="physicalFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Firma física"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Firma física: todas</SelectItem>
                    <SelectItem value="true">Con copia física</SelectItem>
                    <SelectItem value="false">Sin copia física</SelectItem>
                </SelectContent>
            </Select>
            <Input
                v-model="from"
                type="date"
                class="w-full lg:w-40"
                @change="applyFilters"
            />
            <Input
                v-model="to"
                type="date"
                class="w-full lg:w-40"
                @change="applyFilters"
            />
        </FilterBar>

        <EmptyState
            v-if="responsibilities.data.length === 0"
            :icon="FileClock"
            title="No hay responsivas que coincidan"
            description="Se generan automáticamente al asignar un responsable a un activo, o manualmente desde la ficha del activo."
        />

        <div v-else class="space-y-3">
            <div
                v-for="item in responsibilities.data"
                :key="item.id"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Link
                            v-if="item.asset"
                            :href="`/activos/${item.asset.public_id}`"
                            class="font-mono text-xs text-muted-foreground hover:underline"
                        >
                            {{ item.asset.internal_code }}
                        </Link>
                        <p class="text-sm font-medium text-foreground">
                            {{ item.receiver_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ item.asset?.name }}
                            <span v-if="item.document_number">
                                · {{ item.document_number }}</span
                            >
                            <span v-if="item.company">
                                · {{ item.company.name }}</span
                            >
                        </p>
                    </div>
                    <StatusBadge
                        :label="item.status.label"
                        :color="item.status.color"
                    />
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <Badge
                        v-if="item.has_signed_pdf"
                        variant="outline"
                        class="gap-1 text-emerald-700 dark:text-emerald-400"
                    >
                        <FileSignature class="size-3" /> Firma digital
                    </Badge>
                    <Badge
                        v-if="item.has_physical_copy"
                        variant="outline"
                        class="gap-1 text-emerald-700 dark:text-emerald-400"
                    >
                        <FileCheck2 class="size-3" /> Copia física
                    </Badge>
                    <span class="text-xs text-muted-foreground"
                        >Emitida {{ formatDate(item.issued_at) }}</span
                    >

                    <div class="ml-auto">
                        <Link
                            :href="`/responsivas/${item.public_id}`"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                        >
                            <Eye class="size-3.5" /> Ver detalle
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ responsibilities.total }} responsivas
                </p>
                <Pager :links="responsibilities.links" />
            </div>
        </div>
    </div>
</template>
