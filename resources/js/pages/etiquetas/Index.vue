<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckSquare, Download, QrCode, Square } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
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

function printSelected(template: 'standard' | 'compact' = 'standard') {
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
    <Head title="Etiquetas QR" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Etiquetas QR" description="Genera e imprime etiquetas con código QR para pegar en los equipos">
            <template #actions>
                <Button variant="outline" :disabled="selected.size === 0" @click="printSelected('compact')">
                    <QrCode class="mr-1 size-4" />
                    3 columnas
                </Button>
                <Button :disabled="selected.size === 0" @click="printSelected('standard')">
                    <Download class="mr-1 size-4" />
                    Imprimir seleccionados ({{ selected.size }})
                </Button>
            </template>
        </PageHeader>

        <FilterBar :search="search" search-placeholder="Buscar activo..." @update:search="applySearch">
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
