<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Download, FileBarChart, FileText, SlidersHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type InventoryFilterOptions = {
    branches: { id: number; name: string; company_id: number }[];
    departments: { id: number; name: string; company_id: number | null }[];
    assetTypes: { id: number; name: string }[];
    brands: { id: number; name: string }[];
    responsiblePeople: { id: number; full_name: string }[];
    statuses: { value: string; label: string }[];
};

const props = defineProps<{
    companies: { id: number; name: string }[];
    reports: { key: string; title: string }[];
    inventoryFilterOptions: InventoryFilterOptions;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reportes', href: '/reportes' }] },
});

const companyFilter = ref('all');
const from = ref<string | null>(null);
const to = ref<string | null>(null);

const showInventoryFilters = ref(false);
const inventoryFilters = ref({
    branch_id: 'all',
    department_id: 'all',
    responsible_id: 'all',
    asset_type_id: 'all',
    brand_id: 'all',
    status: 'all',
    in_inventory: 'all',
});

const branchOptions = computed(() =>
    companyFilter.value === 'all'
        ? props.inventoryFilterOptions.branches
        : props.inventoryFilterOptions.branches.filter((b) => String(b.company_id) === companyFilter.value),
);

const baseQuery = computed(() => {
    const params = new URLSearchParams();

    if (companyFilter.value !== 'all') {
        params.set('company_id', companyFilter.value);
    }

    if (from.value) {
        params.set('from', from.value);
    }

    if (to.value) {
        params.set('to', to.value);
    }

    return params;
});

function exportUrl(key: string, format: 'excel' | 'pdf'): string {
    const params = new URLSearchParams(baseQuery.value);

    if (key === 'inventario') {
        for (const [field, value] of Object.entries(inventoryFilters.value)) {
            if (value !== 'all') {
                params.set(field, value);
            }
        }
    }

    const query = params.toString();

    return `/reportes/${key}/${format}${query ? `?${query}` : ''}`;
}

const descriptions: Record<string, string> = {
    inventario: 'Listado completo de activos con filtros por empresa, sucursal, área, responsable, tipo, marca y estatus.',
    bajas: 'Activos dados de baja, con motivo y fecha.',
    prestamos: 'Historial de préstamos, devoluciones y vencimientos.',
    piezas: 'Piezas y refacciones registradas en el inventario.',
    auditorias: 'Resumen de auditorías realizadas y sus resultados.',
};
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Reportes" description="Genera reportes en Excel o PDF con los filtros que necesites" />

        <div class="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label>Empresa</Label>
                <Select v-model="companyFilter">
                    <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas las empresas</SelectItem>
                        <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="grid gap-2">
                <Label>Desde</Label>
                <DatePicker v-model="from" from-today placeholder="Sin límite" />
            </div>
            <div class="grid gap-2">
                <Label>Hasta</Label>
                <DatePicker v-model="to" from-today placeholder="Sin límite" />
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card">
            <button
                type="button"
                class="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-accent/50"
                @click="showInventoryFilters = !showInventoryFilters"
            >
                <span class="flex items-center gap-2 text-sm font-medium">
                    <SlidersHorizontal class="size-4 text-primary" />
                    Filtros avanzados de inventario general
                </span>
                <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': showInventoryFilters }" />
            </button>
            <div v-if="showInventoryFilters" class="grid gap-4 border-t border-border p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-2">
                    <Label>Sucursal</Label>
                    <Select v-model="inventoryFilters.branch_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las sucursales</SelectItem>
                            <SelectItem v-for="b in branchOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Área</Label>
                    <Select v-model="inventoryFilters.department_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las áreas</SelectItem>
                            <SelectItem v-for="d in inventoryFilterOptions.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Responsable</Label>
                    <Select v-model="inventoryFilters.responsible_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los responsables</SelectItem>
                            <SelectItem v-for="r in inventoryFilterOptions.responsiblePeople" :key="r.id" :value="String(r.id)">{{ r.full_name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Tipo de activo</Label>
                    <Select v-model="inventoryFilters.asset_type_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            <SelectItem v-for="t in inventoryFilterOptions.assetTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Marca</Label>
                    <Select v-model="inventoryFilters.brand_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las marcas</SelectItem>
                            <SelectItem v-for="b in inventoryFilterOptions.brands" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Estatus</Label>
                    <Select v-model="inventoryFilters.status">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estatus</SelectItem>
                            <SelectItem v-for="s in inventoryFilterOptions.statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Sigue en inventario</Label>
                    <Select v-model="inventoryFilters.in_inventory">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="1">Sí, en inventario</SelectItem>
                            <SelectItem value="0">No, dados de baja</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="report in reports" :key="report.key">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileBarChart class="size-4 text-primary" />
                        {{ report.title }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">{{ descriptions[report.key] }}</p>
                    <div class="flex gap-2">
                        <a :href="exportUrl(report.key, 'excel')" class="flex-1">
                            <Button variant="outline" class="w-full">
                                <Download class="mr-1 size-4" />
                                Excel
                            </Button>
                        </a>
                        <a :href="exportUrl(report.key, 'pdf')" target="_blank" class="flex-1">
                            <Button class="w-full">
                                <FileText class="mr-1 size-4" />
                                PDF
                            </Button>
                        </a>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
