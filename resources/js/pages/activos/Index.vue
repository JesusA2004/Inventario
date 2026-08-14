<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Boxes, CheckSquare, Download, Plus, QrCode, Square, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AssetCard from '@/components/assets/AssetCard.vue';
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

function generateLabels(template: 'standard' | 'compact' = 'standard') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/etiquetas/pdf';
    form.target = '_blank';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    form.appendChild(csrf);

    const templateInput = document.createElement('input');
    templateInput.type = 'hidden';
    templateInput.name = 'template';
    templateInput.value = template;
    form.appendChild(templateInput);

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
                <Button variant="ghost" size="sm" :disabled="selected.size === 0" @click="clearSelection">
                    Limpiar selección
                </Button>
                <span class="text-sm text-muted-foreground">{{ selected.size }} seleccionados</span>
            </div>
            <div class="flex gap-2">
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
            <Select
                v-model="filterState.company_id"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Empresa"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas las empresas</SelectItem>
                    <SelectItem
                        v-for="c in filterOptions.companies"
                        :key="c.id"
                        :value="String(c.id)"
                        >{{ c.name }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select
                v-model="filterState.branch_id"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Sucursal"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas las sucursales</SelectItem>
                    <SelectItem
                        v-for="b in branchOptions"
                        :key="b.id"
                        :value="String(b.id)"
                        >{{ b.name }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select
                v-model="filterState.asset_type_id"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="Tipo"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los tipos</SelectItem>
                    <SelectItem
                        v-for="t in filterOptions.assetTypes"
                        :key="t.id"
                        :value="String(t.id)"
                        >{{ t.name }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select
                v-model="filterState.status"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-36"
                    ><SelectValue placeholder="Estatus"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los estatus</SelectItem>
                    <SelectItem
                        v-for="s in filterOptions.statuses"
                        :key="s.value"
                        :value="s.value"
                        >{{ s.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
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
