<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Boxes, CheckSquare, Download, FileArchive, LayoutGrid, List, Plus, QrCode, Square, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AssetCard from '@/components/assets/AssetCard.vue';
import Combobox from '@/components/Combobox.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import LabelSizeDialog from '@/components/labels/LabelSizeDialog.vue';
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
import type { LabelColumns, LabelSizeKey, LabelSizesConfig } from '@/lib/labelSizes';
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
    labelSizes: LabelSizesConfig;
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

function onCompanyFilterChange() {
    // Encadenamiento hacia adelante: si la sucursal/área/responsable
    // seleccionados no pertenecen a la nueva empresa, se limpian.
    const branch = props.filterOptions.branches.find((b) => String(b.id) === filterState.value.branch_id);

    if (branch && String(branch.company_id) !== filterState.value.company_id) {
        filterState.value.branch_id = 'all';
    }

    const department = props.filterOptions.departments.find((d) => String(d.id) === filterState.value.department_id);

    if (department && department.company_id !== null && String(department.company_id) !== filterState.value.company_id) {
        filterState.value.department_id = 'all';
    }

    applyFilters();
}

function onBranchFilterChange() {
    // Encadenamiento inverso: elegir una sucursal directamente autoselecciona
    // su empresa, ya que están ligadas.
    if (filterState.value.branch_id !== 'all') {
        const branch = props.filterOptions.branches.find((b) => String(b.id) === filterState.value.branch_id);

        if (branch) {
            filterState.value.company_id = String(branch.company_id);
        }
    }

    applyFilters();
}

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

function clearFilters() {
    search.value = '';

    for (const key of Object.keys(filterState.value) as (keyof typeof filterState.value)[]) {
        filterState.value[key] = 'all';
    }

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

const viewMode = ref<'cards' | 'table'>('cards');

// Selección para generar etiquetas QR en lote, directamente desde el listado.
// 'ids': un conjunto explícito de activos marcados uno por uno (o "toda la
// página"). 'all_filtered': el usuario pidió "los N resultados filtrados" —
// no se guarda una lista de IDs, solo se recuerda el modo; al generar el
// PDF/ZIP se vuelven a mandar los filtros actuales y el backend vuelve a
// correr la misma consulta, así que funciona sin importar cuántos sean.
const qrMode = ref(false);
const selectionMode = ref<'ids' | 'all_filtered'>('ids');
const selected = ref<Set<number>>(new Set());

function toggleQrMode() {
    qrMode.value = !qrMode.value;
    selectionMode.value = 'ids';
    selected.value = new Set();
}

function currentFilterParams(): Record<string, string | undefined> {
    const params: Record<string, string | undefined> = { q: search.value || undefined };

    for (const [key, value] of Object.entries(filterState.value)) {
        params[key] = value !== 'all' ? value : undefined;
    }

    return params;
}

/**
 * Si estamos en modo "todos los filtrados" y el usuario toca un checkbox
 * puntual, hay que materializar esa selección abstracta en IDs concretos
 * antes de poder quitar uno solo.
 */
async function materializeAllFilteredSelection() {
    const result = await getJson<{ ids: number[]; total: number }>('/activos/ids-filtrados', currentFilterParams());
    selectionMode.value = 'ids';
    selected.value = new Set(result.ids);
}

function isSelected(id: number): boolean {
    return selectionMode.value === 'all_filtered' || selected.value.has(id);
}

async function toggleSelect(id: number) {
    if (selectionMode.value === 'all_filtered') {
        await materializeAllFilteredSelection();
    }

    if (selected.value.has(id)) {
        selected.value.delete(id);
    } else {
        selected.value.add(id);
    }

    selected.value = new Set(selected.value);
}

const allOnPageSelected = computed(
    () =>
        props.assets.data.length > 0 &&
        (selectionMode.value === 'all_filtered' || props.assets.data.every((a) => selected.value.has(a.id))),
);

function selectAllOnPage() {
    selectionMode.value = 'ids';
    props.assets.data.forEach((asset) => selected.value.add(asset.id));
    selected.value = new Set(selected.value);
}

function clearSelection() {
    selectionMode.value = 'ids';
    selected.value = new Set();
}

function selectAllFiltered() {
    selectionMode.value = 'all_filtered';
    selected.value = new Set();
}

const selectedCount = computed(() => (selectionMode.value === 'all_filtered' ? props.assets.total : selected.value.size));

function submitSelectionForm(action: string, extraFields: Record<string, string> = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.target = '_blank';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    form.appendChild(csrf);

    const fields: Record<string, string | undefined> = {
        ...extraFields,
        selection_mode: selectionMode.value,
        ...(selectionMode.value === 'all_filtered' ? currentFilterParams() : {}),
    };

    for (const [name, value] of Object.entries(fields)) {
        if (value === undefined) {
            continue;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    if (selectionMode.value === 'ids') {
        selected.value.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'asset_ids[]';
            input.value = String(id);
            form.appendChild(input);
        });
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

const sizeDialogOpen = ref(false);

const firstSelectedAsset = computed(() => {
    if (selectionMode.value === 'all_filtered') {
        return props.assets.data[0] ?? null;
    }

    return props.assets.data.find((asset) => selected.value.has(asset.id)) ?? null;
});

const previewAsset = computed(() =>
    firstSelectedAsset.value
        ? {
              type_name: (firstSelectedAsset.value.assetType?.name ?? '').toUpperCase(),
              internal_code: firstSelectedAsset.value.internal_code,
              serial_number: firstSelectedAsset.value.serial_number,
              company_name: firstSelectedAsset.value.company?.name ?? '',
              qr_image_url: `/activos/${firstSelectedAsset.value.public_id}/qr`,
          }
        : null,
);

function generateLabels(payload: { size: LabelSizeKey; columns: LabelColumns; widthMm: number; heightMm: number }) {
    submitSelectionForm('/etiquetas/pdf', {
        template: payload.columns === 3 ? 'compact' : 'standard',
        size: payload.size,
        width_mm: String(payload.widthMm),
        height_mm: String(payload.heightMm),
    });
}

function downloadQrZip() {
    submitSelectionForm('/activos-qr-zip');
}
</script>

<template>
    <Head title="Activos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Activos"
            description="Inventario completo de equipos de TI"
            help-text="Un activo es un equipo completo (laptop, monitor, impresora). Sus componentes internos o accesorios sueltos se registran como piezas y se vinculan aquí desde la pestaña Piezas."
        >
            <template #actions>
                <div class="flex items-center rounded-lg border border-border p-0.5">
                    <button
                        type="button"
                        class="rounded-md p-1.5 transition-colors"
                        :class="viewMode === 'cards' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground'"
                        title="Vista de tarjetas"
                        @click="viewMode = 'cards'"
                    >
                        <LayoutGrid class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded-md p-1.5 transition-colors"
                        :class="viewMode === 'table' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground'"
                        title="Vista de tabla"
                        @click="viewMode = 'table'"
                    >
                        <List class="size-4" />
                    </button>
                </div>
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
                <Button variant="ghost" size="sm" @click="selectAllFiltered">
                    <CheckSquare class="mr-1 size-4" />
                    Seleccionar los {{ assets.total }} resultados
                </Button>
                <Button variant="ghost" size="sm" :disabled="selectedCount === 0" @click="clearSelection">
                    Limpiar selección
                </Button>
                <span class="text-sm text-muted-foreground">
                    {{ selectedCount }} seleccionados
                    <template v-if="selectionMode === 'all_filtered'">(todos los resultados filtrados)</template>
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" :disabled="selectedCount === 0" @click="downloadQrZip">
                    <FileArchive class="mr-1 size-4" />
                    Descargar QR (.zip)
                </Button>
                <Button size="sm" :disabled="selectedCount === 0" @click="sizeDialogOpen = true">
                    <Download class="mr-1 size-4" />
                    Generar etiquetas ({{ selectedCount }})
                </Button>
            </div>
        </div>

        <LabelSizeDialog
            v-model:open="sizeDialogOpen"
            :config="labelSizes"
            :count="selectedCount"
            :preview-asset="previewAsset"
            @confirm="generateLabels"
        />

        <FilterBar
            :search="search"
            search-placeholder="Buscar por clave, dispositivo, serie o modelo..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Combobox
                v-model="filterState.company_id"
                :options="companyComboOptions"
                placeholder="Empresa"
                search-placeholder="Buscar empresa..."
                class="w-full lg:w-40"
                @update:model-value="onCompanyFilterChange"
            />
            <Combobox
                v-model="filterState.branch_id"
                :options="branchComboOptions"
                placeholder="Sucursal"
                search-placeholder="Buscar sucursal..."
                class="w-full lg:w-40"
                @update:model-value="onBranchFilterChange"
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
            <!-- Galería de tarjetas con foto (vista por defecto) -->
            <div v-if="viewMode === 'cards'" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <AssetCard
                    v-for="asset in assets.data"
                    :key="asset.id"
                    :asset="asset"
                    :selectable="qrMode"
                    :selected="isSelected(asset.id)"
                    @toggle-select="toggleSelect"
                />
            </div>

            <!-- Tabla (vista opcional, más densa) -->
            <div
                v-else
                class="overflow-x-auto rounded-xl border border-border bg-card"
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
                            :class="qrMode && isSelected(asset.id) && 'bg-accent/40'"
                            @click="qrMode ? toggleSelect(asset.id) : router.visit(`/activos/${asset.public_id}`)"
                        >
                            <TableCell v-if="qrMode" @click.stop="toggleSelect(asset.id)">
                                <Checkbox :model-value="isSelected(asset.id)" />
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
