<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Boxes, CheckSquare, Download, FileArchive, Plus, QrCode, Square, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AssetCard from '@/components/assets/AssetCard.vue';
import Combobox from '@/components/Combobox.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { getJson } from '@/lib/http';
import type { AssetListItem, Paginated, StatusOption } from '@/types/assets';

type FilterOptions = {
    companies: { id: number; name: string }[];
    branches: { id: number; name: string; company_id: number }[];
    departments: { id: number; name: string; company_id: number | null }[];
    assetTypes: { id: number; name: string }[];
    brands: { id: number; name: string }[];
    responsiblePeople: { id: number; full_name: string }[];
    statuses: StatusOption[];
};

const props = defineProps<{
    assets: Paginated<AssetListItem>;
    filters: Record<string, string | undefined>;
    filterOptions: FilterOptions;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Activos', href: '/activos' }] },
});

const search = ref(props.filters.q ?? '');
const filterState = ref({
    company_id: props.filters.company_id ?? 'all',
    branch_id: props.filters.branch_id ?? 'all',
    department_id: props.filters.department_id ?? 'all',
    asset_type_id: props.filters.asset_type_id ?? 'all',
    brand_id: props.filters.brand_id ?? 'all',
    responsible_id: props.filters.responsible_id ?? 'all',
    status: props.filters.status ?? 'all',
    in_inventory: props.filters.in_inventory ?? 'all',
});

const branchOptions = computed(() =>
    filterState.value.company_id === 'all'
        ? props.filterOptions.branches
        : props.filterOptions.branches.filter(
              (b) => String(b.company_id) === filterState.value.company_id,
          ),
);

const companyComboOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.filterOptions.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);
const branchComboOptions = computed(() => [
    { value: 'all', label: 'Todas las sucursales' },
    ...branchOptions.value.map((b) => ({ value: String(b.id), label: b.name })),
]);
const departmentComboOptions = computed(() => [
    { value: 'all', label: 'Todas las áreas' },
    ...props.filterOptions.departments.map((d) => ({ value: String(d.id), label: d.name })),
]);
const assetTypeComboOptions = computed(() => [
    { value: 'all', label: 'Todos los tipos' },
    ...props.filterOptions.assetTypes.map((t) => ({ value: String(t.id), label: t.name })),
]);
const brandComboOptions = computed(() => [
    { value: 'all', label: 'Todas las marcas' },
    ...props.filterOptions.brands.map((b) => ({ value: String(b.id), label: b.name })),
]);
const responsibleComboOptions = computed(() => [
    { value: 'all', label: 'Todos los responsables' },
    ...props.filterOptions.responsiblePeople.map((r) => ({ value: String(r.id), label: r.full_name })),
]);
const statusComboOptions = computed(() => [
    { value: 'all', label: 'Todos los estatus' },
    ...props.filterOptions.statuses.map((s) => ({ value: s.value, label: s.label })),
]);

function applyFilters() {
    const params: Record<string, string | undefined> = {
        q: search.value || undefined,
    };

    for (const [key, value] of Object.entries(filterState.value)) {
        params[key] = value !== 'all' ? value : undefined;
    }

    router.get('/activos', params, { preserveState: true, replace: true });
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

const activeFiltersCount = computed(
    () => Object.values(filterState.value).filter((v) => v !== 'all').length,
);

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (search.value) {
        params.set('q', search.value);
    }

    for (const [key, value] of Object.entries(filterState.value)) {
        if (value !== 'all') {
            params.set(key, value);
        }
    }

    return `/activos-exportar?${params.toString()}`;
});

// Selección para generar etiquetas QR en lote, directamente desde el listado.
const qrMode = ref(false);
const selected = ref<Set<number>>(new Set());

function toggleQrMode() {
    qrMode.value = !qrMode.value;
    selected.value = new Set();
}

function toggleSelect(id: number) {
    if (selected.value.has(id)) {
        selected.value.delete(id);
    } else {
        selected.value.add(id);
    }

    selected.value = new Set(selected.value);
}

const allOnPageSelected = computed(
    () => props.assets.data.length > 0 && props.assets.data.every((a) => selected.value.has(a.id)),
);

function selectAllOnPage() {
    props.assets.data.forEach((asset) => selected.value.add(asset.id));
    selected.value = new Set(selected.value);
}

function clearSelection() {
    selected.value = new Set();
}

const selectingAllFiltered = ref(false);

async function selectAllFiltered() {
    selectingAllFiltered.value = true;

    try {
        const params: Record<string, string | undefined> = { q: search.value || undefined };

        for (const [key, value] of Object.entries(filterState.value)) {
            params[key] = value !== 'all' ? value : undefined;
        }

        const result = await getJson<{ ids: number[]; truncated: boolean }>('/activos/ids-filtrados', params);
        selected.value = new Set(result.ids);
    } finally {
        selectingAllFiltered.value = false;
    }
}

function submitAssetIdsForm(action: string, extraFields: Record<string, string> = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.target = '_blank';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    form.appendChild(csrf);

    for (const [name, value] of Object.entries(extraFields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    selected.value.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'asset_ids[]';
        input.value = String(id);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function generateLabels(template: 'standard' | 'compact' = 'standard') {
    submitAssetIdsForm('/etiquetas/pdf', { template });
}

function downloadQrZip() {
    submitAssetIdsForm('/activos-qr-zip');
}
</script>

<template>
    <Head title="Activos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Activos"
            description="Inventario completo de equipos de TI"
        >
            <template #actions>
                <Button :variant="qrMode ? 'default' : 'outline'" @click="toggleQrMode">
                    <component :is="qrMode ? X : QrCode" class="mr-1 size-4" />
                    {{ qrMode ? 'Cancelar selección' : 'Generar etiquetas QR' }}
                </Button>
                <a :href="exportUrl">
                    <Button variant="outline">
                        <Download class="mr-1 size-4" />
                        Exportar
                    </Button>
                </a>
                <Link href="/activos/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nuevo activo
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <div
            v-if="qrMode"
            class="flex flex-col gap-3 rounded-xl border border-primary/30 bg-accent/40 p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="ghost" size="sm" @click="allOnPageSelected ? clearSelection() : selectAllOnPage()">
                    <component :is="allOnPageSelected ? CheckSquare : Square" class="mr-1 size-4" />
                    Seleccionar página
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    :disabled="selectingAllFiltered"
                    @click="selectAllFiltered"
                >
                    <CheckSquare class="mr-1 size-4" />
                    {{ selectingAllFiltered ? 'Buscando...' : `Seleccionar los ${assets.total} resultados` }}
                </Button>
                <Button variant="ghost" size="sm" :disabled="selected.size === 0" @click="clearSelection">
                    Limpiar selección
                </Button>
                <span class="text-sm text-muted-foreground">{{ selected.size }} seleccionados</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" :disabled="selected.size === 0" @click="downloadQrZip">
                    <FileArchive class="mr-1 size-4" />
                    Descargar QR (.zip)
                </Button>
                <Button variant="outline" size="sm" :disabled="selected.size === 0" @click="generateLabels('compact')">
                    <QrCode class="mr-1 size-4" />
                    3 columnas
                </Button>
                <Button size="sm" :disabled="selected.size === 0" @click="generateLabels('standard')">
                    <Download class="mr-1 size-4" />
                    Generar etiquetas ({{ selected.size }})
                </Button>
            </div>
        </div>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por clave, dispositivo, serie o modelo..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
        >
            <Combobox
                v-model="filterState.company_id"
                :options="companyComboOptions"
                placeholder="Empresa"
                search-placeholder="Buscar empresa..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.branch_id"
                :options="branchComboOptions"
                placeholder="Sucursal"
                search-placeholder="Buscar sucursal..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.department_id"
                :options="departmentComboOptions"
                placeholder="Área"
                search-placeholder="Buscar área..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.asset_type_id"
                :options="assetTypeComboOptions"
                placeholder="Tipo"
                search-placeholder="Buscar tipo..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.brand_id"
                :options="brandComboOptions"
                placeholder="Marca"
                search-placeholder="Buscar marca..."
                class="w-full lg:w-40"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.responsible_id"
                :options="responsibleComboOptions"
                placeholder="Responsable"
                search-placeholder="Buscar responsable..."
                class="w-full lg:w-44"
                @update:model-value="applyFilters"
            />
            <Combobox
                v-model="filterState.status"
                :options="statusComboOptions"
                placeholder="Estatus"
                search-placeholder="Buscar estatus..."
                class="w-full lg:w-36"
                @update:model-value="applyFilters"
            />
            <Select
                v-model="filterState.in_inventory"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Inventario"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Inventario: todos</SelectItem>
                    <SelectItem value="1">En inventario</SelectItem>
                    <SelectItem value="0">Dados de baja</SelectItem>
                </SelectContent>
            </Select>
        </FilterBar>

        <EmptyState
            v-if="assets.data.length === 0"
            :icon="Boxes"
            title="Todavía no hay activos registrados"
            description="Empieza registrando el primer dispositivo del inventario."
        >
            <template #action>
                <Link href="/activos/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Registrar activo
                    </Button>
                </Link>
            </template>
        </EmptyState>

        <template v-else>
            <!-- Mobile: cards -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:hidden">
                <AssetCard
                    v-for="asset in assets.data"
                    :key="asset.id"
                    :asset="asset"
                    :selectable="qrMode"
                    :selected="selected.has(asset.id)"
                    @toggle-select="toggleSelect"
                />
            </div>

            <!-- Desktop: table -->
            <div
                class="hidden overflow-x-auto rounded-xl border border-border bg-card lg:block"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="qrMode" class="w-10">
                                <Checkbox
                                    :model-value="allOnPageSelected"
                                    @update:model-value="allOnPageSelected ? clearSelection() : selectAllOnPage()"
                                />
                            </TableHead>
                            <TableHead>Clave</TableHead>
                            <TableHead>Dispositivo</TableHead>
                            <TableHead>Empresa</TableHead>
                            <TableHead>Sucursal</TableHead>
                            <TableHead>Área</TableHead>
                            <TableHead>Marca</TableHead>
                            <TableHead>Serie</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Última revisión</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="asset in assets.data"
                            :key="asset.id"
                            class="cursor-pointer"
                            :class="qrMode && selected.has(asset.id) && 'bg-accent/40'"
                            @click="qrMode ? toggleSelect(asset.id) : router.visit(`/activos/${asset.public_id}`)"
                        >
                            <TableCell v-if="qrMode" @click.stop="toggleSelect(asset.id)">
                                <Checkbox :model-value="selected.has(asset.id)" />
                            </TableCell>
                            <TableCell class="font-mono text-sm">{{
                                asset.internal_code
                            }}</TableCell>
                            <TableCell class="font-medium text-foreground">{{
                                asset.name
                            }}</TableCell>
                            <TableCell>{{ asset.company?.name }}</TableCell>
                            <TableCell>{{ asset.branch?.name }}</TableCell>
                            <TableCell>{{
                                asset.department?.name ?? '—'
                            }}</TableCell>
                            <TableCell>{{
                                asset.brand?.name ?? '—'
                            }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{
                                asset.serial_number ?? '—'
                            }}</TableCell>
                            <TableCell>{{
                                asset.currentResponsible?.full_name ?? '—'
                            }}</TableCell>
                            <TableCell>
                                <StatusBadge
                                    v-if="asset.status"
                                    :label="asset.status.label"
                                    :color="asset.status.color"
                                />
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{
                                asset.last_reviewed_at ?? '—'
                            }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ assets.total }} activos encontrados
                </p>
                <Pager :links="assets.links" />
            </div>
        </template>
    </div>
</template>
