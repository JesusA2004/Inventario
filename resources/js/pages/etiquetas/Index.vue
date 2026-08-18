<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckSquare, Download, QrCode, Square } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import LabelSizeDialog from '@/components/labels/LabelSizeDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { LabelColumns, LabelSizeKey, LabelSizesConfig } from '@/lib/labelSizes';
import type { AssetListItem, Paginated } from '@/types/assets';

type FilterOptions = {
    companies: { id: number; name: string }[];
    branches: { id: number; name: string; company_id: number }[];
    departments: { id: number; name: string; company_id: number | null }[];
};

const props = defineProps<{
    assets: Paginated<AssetListItem>;
    filters: Record<string, string | undefined>;
    filterOptions: FilterOptions;
    labelSizes: LabelSizesConfig;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Etiquetas QR', href: '/etiquetas' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const recentOnly = ref(props.filters.recent === '1');

function applyFilters() {
    router.get(
        '/etiquetas',
        {
            q: search.value || undefined,
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
            branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
            recent: recentOnly.value ? '1' : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

function toggleRecent() {
    recentOnly.value = !recentOnly.value;
    applyFilters();
}

const activeFiltersCount = computed(
    () => (companyFilter.value !== 'all' ? 1 : 0) + (branchFilter.value !== 'all' ? 1 : 0) + (recentOnly.value ? 1 : 0),
);

function clearFilters() {
    search.value = '';
    companyFilter.value = 'all';
    branchFilter.value = 'all';
    recentOnly.value = false;
    applyFilters();
}

const selected = ref<Set<number>>(new Set());

function toggle(id: number) {
    if (selected.value.has(id)) {
        selected.value.delete(id);
    } else {
        selected.value.add(id);
    }

    selected.value = new Set(selected.value);
}

function selectAllOnPage() {
    props.assets.data.forEach((asset) => selected.value.add(asset.id));
    selected.value = new Set(selected.value);
}

function clearSelection() {
    selected.value = new Set();
}

const allOnPageSelected = computed(() => props.assets.data.length > 0 && props.assets.data.every((a) => selected.value.has(a.id)));

const sizeDialogOpen = ref(false);

const firstSelectedAsset = computed(() => props.assets.data.find((asset) => selected.value.has(asset.id)) ?? null);

const previewAsset = computed(() =>
    firstSelectedAsset.value
        ? {
              type_name: (firstSelectedAsset.value.assetType?.name ?? '').toUpperCase(),
              name: firstSelectedAsset.value.name,
              internal_code: firstSelectedAsset.value.internal_code,
              serial_number: firstSelectedAsset.value.serial_number,
              company_name: firstSelectedAsset.value.company?.name ?? '',
              qr_image_url: `/activos/${firstSelectedAsset.value.public_id}/qr`,
          }
        : null,
);

function printSelected(payload: { size: LabelSizeKey; columns: LabelColumns; widthMm: number; heightMm: number }) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/etiquetas/pdf';
    form.target = '_blank';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    form.appendChild(csrf);

    const fields: Record<string, string> = {
        columns: String(payload.columns),
        size: payload.size,
        width_mm: String(payload.widthMm),
        height_mm: String(payload.heightMm),
    };

    for (const [name, value] of Object.entries(fields)) {
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
</script>

<template>
    <Head title="Etiquetas QR" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Etiquetas QR"
            description="Genera e imprime etiquetas con código QR para pegar en los equipos"
            help-text="Selecciona uno o varios activos y elige el tamaño de etiqueta antes de generar el PDF: pequeño para accesorios, grande para equipo voluminoso. El QR es permanente aunque edites los datos del activo."
        >
            <template #actions>
                <Button :disabled="selected.size === 0" @click="sizeDialogOpen = true">
                    <Download class="mr-1 size-4" />
                    Generar etiquetas ({{ selected.size }})
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar activo..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Select v-model="companyFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-44"><SelectValue placeholder="Empresa" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas las empresas</SelectItem>
                    <SelectItem v-for="c in filterOptions.companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="branchFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-44"><SelectValue placeholder="Sucursal" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas las sucursales</SelectItem>
                    <SelectItem v-for="b in filterOptions.branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                </SelectContent>
            </Select>
            <Button :variant="recentOnly ? 'default' : 'outline'" @click="toggleRecent">Recién creados</Button>

            <template #actions>
                <Button variant="ghost" size="sm" @click="allOnPageSelected ? clearSelection() : selectAllOnPage()">
                    <component :is="allOnPageSelected ? CheckSquare : Square" class="mr-1 size-4" />
                    Seleccionar página
                </Button>
                <Button variant="ghost" size="sm" :disabled="selected.size === 0" @click="clearSelection">Limpiar selección</Button>
            </template>
        </FilterBar>

        <LabelSizeDialog
            v-model:open="sizeDialogOpen"
            :config="labelSizes"
            :count="selected.size"
            :preview-asset="previewAsset"
            @confirm="printSelected"
        />

        <EmptyState
            v-if="assets.data.length === 0"
            :icon="QrCode"
            title="No hay activos que coincidan con los filtros"
            description="Ajusta la búsqueda o registra nuevos activos para generar sus etiquetas."
        />

        <template v-else>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <label
                    v-for="asset in assets.data"
                    :key="asset.id"
                    class="flex cursor-pointer flex-col gap-2 rounded-xl border bg-card p-3 transition-colors"
                    :class="selected.has(asset.id) ? 'border-primary ring-1 ring-primary' : 'border-border'"
                >
                    <div class="flex items-start justify-between gap-2">
                        <Checkbox :model-value="selected.has(asset.id)" @update:model-value="() => toggle(asset.id)" />
                        <QrCode class="size-4 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-foreground">{{ asset.name }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ asset.internal_code }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ asset.company?.name }}</p>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">{{ assets.total }} activos · {{ selected.size }} seleccionados</p>
                <Pager :links="assets.links" />
            </div>
        </template>
    </div>
</template>
