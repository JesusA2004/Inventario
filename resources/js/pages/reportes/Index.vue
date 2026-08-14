<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, FileBarChart, FileText, RefreshCw } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import HorizontalBarList from '@/components/charts/HorizontalBarList.vue';
import DatePicker from '@/components/DatePicker.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
import { getJson } from '@/lib/http';

type InventoryFilterOptions = {
    branches: { id: number; name: string; company_id: number }[];
    departments: { id: number; name: string; company_id: number | null }[];
    assetTypes: { id: number; name: string }[];
    brands: { id: number; name: string }[];
    responsiblePeople: { id: number; full_name: string }[];
    statuses: { value: string; label: string }[];
};

type InventoryRow = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    company: string | null;
    branch: string | null;
    department: string | null;
    brand: string | null;
    asset_type: string | null;
    responsible: string | null;
    status: { label: string; color: string };
    in_inventory: boolean;
};

type InventoryData = {
    total: number;
    inInventory: number;
    rows: InventoryRow[];
    byStatus: { label: string; color: string; total: number }[];
    byBranch: { name: string; total: number }[];
    byType: { name: string; total: number }[];
    byCompany: { name: string; total: number }[];
};

const props = defineProps<{
    companies: { id: number; name: string }[];
    reports: { key: string; title: string }[];
    inventoryFilterOptions: InventoryFilterOptions;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reportes', href: '/reportes' }] },
});

const otherReports = computed(() => props.reports.filter((r) => r.key !== 'inventario'));

const companyFilter = ref('all');
const from = ref<string | null>(null);
const to = ref<string | null>(null);

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

const inventoryQuery = computed(() => {
    const params = new URLSearchParams(baseQuery.value);

    for (const [field, value] of Object.entries(inventoryFilters.value)) {
        if (value !== 'all') {
            params.set(field, value);
        }
    }

    return params;
});

function exportUrl(key: string, format: 'excel' | 'pdf'): string {
    const params = key === 'inventario' ? inventoryQuery.value : baseQuery.value;
    const query = params.toString();

    return `/reportes/${key}/${format}${query ? `?${query}` : ''}`;
}

const descriptions: Record<string, string> = {
    bajas: 'Activos dados de baja, con motivo y fecha.',
    prestamos: 'Historial de préstamos, devoluciones y vencimientos.',
    piezas: 'Piezas y refacciones registradas en el inventario.',
    auditorias: 'Resumen de auditorías realizadas y sus resultados.',
};

// Panel de inventario en tiempo real: cada cambio de filtro vuelve a
// consultar exactamente los mismos filtros que usan excel()/pdf(), así que
// lo que se ve en pantalla es siempre lo mismo que se exporta.
const liveData = ref<InventoryData | null>(null);
const loading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

async function loadLiveData() {
    loading.value = true;

    const params = Object.fromEntries(inventoryQuery.value.entries());

    try {
        liveData.value = await getJson<InventoryData>('/reportes/inventario/datos', params);
    } finally {
        loading.value = false;
    }
}

watch(
    inventoryQuery,
    () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadLiveData, 300);
    },
    { deep: true },
);

onMounted(loadLiveData);

const statusColorMap: Record<string, string> = {
    green: '--chart-1',
    blue: '--chart-2',
    red: '--chart-5',
    amber: '--chart-4',
    slate: '--chart-3',
    gray: '--chart-3',
};
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Reportes" description="Analiza el inventario en tiempo real y exporta exactamente lo que ves" />

        <section class="flex flex-col gap-4 rounded-xl border border-border bg-card p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="relative flex size-2.5">
                        <span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-primary opacity-75"
                            :class="{ 'opacity-0': !loading }"
                        />
                        <span class="relative inline-flex size-2.5 rounded-full bg-primary" />
                    </span>
                    <h2 class="text-base font-semibold text-foreground">Inventario general · en tiempo real</h2>
                </div>
                <div class="flex gap-2">
                    <a :href="exportUrl('inventario', 'excel')">
                        <Button variant="outline" size="sm">
                            <Download class="mr-1 size-4" />
                            Excel
                        </Button>
                    </a>
                    <a :href="exportUrl('inventario', 'pdf')" target="_blank">
                        <Button size="sm">
                            <FileText class="mr-1 size-4" />
                            PDF
                        </Button>
                    </a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-2">
                    <Label class="text-xs">Empresa</Label>
                    <Select v-model="companyFilter">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las empresas</SelectItem>
                            <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Sucursal</Label>
                    <Select v-model="inventoryFilters.branch_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las sucursales</SelectItem>
                            <SelectItem v-for="b in branchOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Área</Label>
                    <Select v-model="inventoryFilters.department_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las áreas</SelectItem>
                            <SelectItem v-for="d in inventoryFilterOptions.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Responsable</Label>
                    <Select v-model="inventoryFilters.responsible_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los responsables</SelectItem>
                            <SelectItem v-for="r in inventoryFilterOptions.responsiblePeople" :key="r.id" :value="String(r.id)">{{ r.full_name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Tipo de activo</Label>
                    <Select v-model="inventoryFilters.asset_type_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            <SelectItem v-for="t in inventoryFilterOptions.assetTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Marca</Label>
                    <Select v-model="inventoryFilters.brand_id">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las marcas</SelectItem>
                            <SelectItem v-for="b in inventoryFilterOptions.brands" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Estatus</Label>
                    <Select v-model="inventoryFilters.status">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estatus</SelectItem>
                            <SelectItem v-for="s in inventoryFilterOptions.statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Sigue en inventario</Label>
                    <Select v-model="inventoryFilters.in_inventory">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="1">Sí, en inventario</SelectItem>
                            <SelectItem value="0">No, dados de baja</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Desde</Label>
                    <DatePicker v-model="from" from-today placeholder="Sin límite" />
                </div>
                <div class="grid gap-2">
                    <Label class="text-xs">Hasta</Label>
                    <DatePicker v-model="to" from-today placeholder="Sin límite" />
                </div>
            </div>

            <div
                class="grid gap-4 transition-opacity duration-200"
                :class="{ 'opacity-60': loading }"
            >
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <StatCard title="Activos que coinciden" :value="liveData?.total ?? 0" :icon="FileBarChart" />
                    <StatCard title="En inventario" :value="liveData?.inInventory ?? 0" tone="positive" :icon="RefreshCw" />
                    <StatCard title="Dados de baja" :value="(liveData?.total ?? 0) - (liveData?.inInventory ?? 0)" tone="warning" :icon="RefreshCw" />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Por estatus</CardTitle></CardHeader>
                        <CardContent>
                            <DonutChart
                                :data="(liveData?.byStatus ?? []).map((s) => ({ label: s.label, value: s.total, colorVar: statusColorMap[s.color] ?? '--chart-3' }))"
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Por sucursal</CardTitle></CardHeader>
                        <CardContent>
                            <HorizontalBarList :data="(liveData?.byBranch ?? []).map((b) => ({ label: b.name, value: b.total }))" color-var="--chart-2" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Por tipo de activo</CardTitle></CardHeader>
                        <CardContent>
                            <HorizontalBarList :data="(liveData?.byType ?? []).map((t) => ({ label: t.name, value: t.total }))" color-var="--chart-3" />
                        </CardContent>
                    </Card>
                </div>

                <div class="overflow-x-auto rounded-xl border border-border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Clave</th>
                                <th class="px-3 py-2 text-left font-medium">Dispositivo</th>
                                <th class="px-3 py-2 text-left font-medium">Empresa</th>
                                <th class="px-3 py-2 text-left font-medium">Sucursal</th>
                                <th class="px-3 py-2 text-left font-medium">Responsable</th>
                                <th class="px-3 py-2 text-left font-medium">Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="(liveData?.rows.length ?? 0) === 0">
                                <td colspan="6" class="px-3 py-6 text-center text-muted-foreground">
                                    {{ loading ? 'Cargando...' : 'Sin activos que coincidan con estos filtros.' }}
                                </td>
                            </tr>
                            <tr
                                v-for="row in liveData?.rows ?? []"
                                :key="row.id"
                                class="border-t border-border transition-colors hover:bg-muted/40"
                            >
                                <td class="px-3 py-2 font-mono text-xs">{{ row.internal_code }}</td>
                                <td class="px-3 py-2 font-medium text-foreground">{{ row.name }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ row.company ?? '—' }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ row.branch ?? '—' }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ row.responsible ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <StatusBadge :label="row.status.label" :color="row.status.color" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="liveData && liveData.total > liveData.rows.length" class="text-xs text-muted-foreground">
                    Mostrando {{ liveData.rows.length }} de {{ liveData.total }} activos. Exporta a Excel o PDF para ver el listado completo.
                </p>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="report in otherReports" :key="report.key">
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
