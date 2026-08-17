<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftRight, Link as LinkIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import Combobox from '@/components/Combobox.vue';
import DatePicker from '@/components/DatePicker.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated } from '@/types/assets';

type Movement = {
    id: number;
    type: { value: string; label: string };
    field: string | null;
    old_value: string | null;
    new_value: string | null;
    comment: string | null;
    created_at: string | null;
    user: string | null;
    asset: {
        public_id: string;
        internal_code: string;
        name: string;
        company: string | null;
        branch: string | null;
    } | null;
};

type FilterOptions = {
    companies: { id: number; name: string }[];
    branches: { id: number; name: string; company_id: number }[];
    users: { id: number; name: string }[];
    types: { value: string; label: string }[];
};

const props = defineProps<{
    movements: Paginated<Movement>;
    filters: Record<string, string | undefined>;
    filterOptions: FilterOptions;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Movimientos', href: '/movimientos' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const userFilter = ref(props.filters.user_id ?? 'all');
const typeFilter = ref(props.filters.type ?? 'all');
const from = ref<string | null>(props.filters.from ?? null);
const to = ref<string | null>(props.filters.to ?? null);

const branchOptions = computed(() =>
    companyFilter.value === 'all'
        ? props.filterOptions.branches
        : props.filterOptions.branches.filter((b) => String(b.company_id) === companyFilter.value),
);

const companyComboOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.filterOptions.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);
const branchComboOptions = computed(() => [
    { value: 'all', label: 'Todas las sucursales' },
    ...branchOptions.value.map((b) => ({ value: String(b.id), label: b.name })),
]);
const userComboOptions = computed(() => [
    { value: 'all', label: 'Todos los usuarios' },
    ...props.filterOptions.users.map((u) => ({ value: String(u.id), label: u.name })),
]);
const typeComboOptions = computed(() => [
    { value: 'all', label: 'Todos los tipos' },
    ...props.filterOptions.types.map((t) => ({ value: t.value, label: t.label })),
]);

function applyFilters() {
    router.get(
        '/movimientos',
        {
            q: search.value || undefined,
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
            branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
            user_id: userFilter.value !== 'all' ? userFilter.value : undefined,
            type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
            from: from.value || undefined,
            to: to.value || undefined,
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
    branchFilter.value = 'all';
    userFilter.value = 'all';
    typeFilter.value = 'all';
    from.value = null;
    to.value = null;
    applyFilters();
}

function onCompanyChange() {
    const branch = props.filterOptions.branches.find((b) => String(b.id) === branchFilter.value);

    if (branch && String(branch.company_id) !== companyFilter.value) {
        branchFilter.value = 'all';
    }

    applyFilters();
}

function onBranchChange() {
    if (branchFilter.value !== 'all') {
        const branch = props.filterOptions.branches.find((b) => String(b.id) === branchFilter.value);

        if (branch) {
            companyFilter.value = String(branch.company_id);
        }
    }

    applyFilters();
}

const activeFiltersCount = computed(
    () =>
        [companyFilter.value, branchFilter.value, userFilter.value, typeFilter.value].filter((v) => v !== 'all').length +
        (from.value ? 1 : 0) +
        (to.value ? 1 : 0),
);

function formatDate(value: string | null): string {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<template>
    <Head title="Movimientos" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Movimientos" description="Historial global de cambios sobre todos los activos" />

        <FilterBar
            :search="search"
            search-placeholder="Buscar por clave o nombre del activo..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Combobox
                v-model="companyFilter"
                :options="companyComboOptions"
                placeholder="Empresa"
                search-placeholder="Buscar empresa..."
                class="w-full lg:w-40"
                @update:model-value="onCompanyChange"
            />
            <Combobox
                v-model="branchFilter"
                :options="branchComboOptions"
                placeholder="Sucursal"
                search-placeholder="Buscar sucursal..."
                class="w-full lg:w-40"
                @update:model-value="onBranchChange"
            />
            <Combobox
                v-model="userFilter"
                :options="userComboOptions"
                placeholder="Usuario"
                search-placeholder="Buscar usuario..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="typeFilter"
                :options="typeComboOptions"
                placeholder="Tipo"
                search-placeholder="Buscar tipo..."
                class="w-full lg:w-44"
                @update:model-value="applyFilters"
            />
            <DatePicker v-model="from" placeholder="Desde" @update:model-value="applyFilters" />
            <DatePicker v-model="to" placeholder="Hasta" @update:model-value="applyFilters" />
        </FilterBar>

        <EmptyState
            v-if="movements.data.length === 0"
            :icon="ArrowLeftRight"
            title="No hay movimientos que coincidan con los filtros"
            description="Los movimientos se registran automáticamente al crear, editar, prestar o dar de baja un activo."
        />

        <template v-else>
            <div class="overflow-x-auto rounded-xl border border-border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha/hora</TableHead>
                            <TableHead>Activo</TableHead>
                            <TableHead>Empresa</TableHead>
                            <TableHead>Sucursal</TableHead>
                            <TableHead>Usuario</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Anterior</TableHead>
                            <TableHead>Nuevo</TableHead>
                            <TableHead>Comentario</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="movement in movements.data" :key="movement.id">
                            <TableCell class="text-sm text-muted-foreground whitespace-nowrap">{{ formatDate(movement.created_at) }}</TableCell>
                            <TableCell>
                                <a
                                    v-if="movement.asset?.public_id"
                                    :href="`/activos/${movement.asset.public_id}`"
                                    class="inline-flex items-center gap-1 font-mono text-sm text-primary hover:underline"
                                >
                                    <LinkIcon class="size-3" />
                                    {{ movement.asset.internal_code }}
                                </a>
                                <span v-else class="text-muted-foreground">—</span>
                                <p v-if="movement.asset" class="text-xs text-muted-foreground">{{ movement.asset.name }}</p>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ movement.asset?.company ?? '—' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ movement.asset?.branch ?? '—' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ movement.user ?? 'Sistema' }}</TableCell>
                            <TableCell class="text-sm font-medium text-foreground">{{ movement.type.label }}</TableCell>
                            <TableCell class="max-w-40 truncate text-sm text-muted-foreground">{{ movement.old_value ?? '—' }}</TableCell>
                            <TableCell class="max-w-40 truncate text-sm text-muted-foreground">{{ movement.new_value ?? '—' }}</TableCell>
                            <TableCell class="max-w-56 truncate text-sm text-muted-foreground">{{ movement.comment ?? '—' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">{{ movements.total }} movimientos encontrados</p>
                <Pager :links="movements.links" />
            </div>
        </template>
    </div>
</template>
