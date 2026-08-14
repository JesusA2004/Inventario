<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Boxes,
    ClipboardCheck,
    PackageCheck,
    Plus,
    QrCode,
    Search,
    ShieldAlert,
    Trash2,
    UserRoundX,
    Wrench,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import HorizontalBarList from '@/components/charts/HorizontalBarList.vue';
import MonthlyTrendChart from '@/components/charts/MonthlyTrendChart.vue';
import Combobox from '@/components/Combobox.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getJson } from '@/lib/http';
import { dashboard } from '@/routes';

type Stats = {
    total: number;
    inInventory: number;
    decommissioned: number;
    damaged: number;
    inReview: number;
    activeLoans: number;
    overdueLoans: number;
    availableParts: number;
};

type NamedTotal = { name: string; total: number };
type StatusTotal = { label: string; color: string; total: number };

type ChartData = {
    byCompany: NamedTotal[];
    byType: NamedTotal[];
    byStatus: StatusTotal[];
    byBranch: NamedTotal[];
    byDepartment: NamedTotal[];
    byLoanStatus: StatusTotal[];
    byPartStatus: StatusTotal[];
    byResponsible: { full_name: string; total: number }[];
    monthly: { month: string; altas: number; bajas: number }[];
};

type DashboardData = { stats: Stats; charts: ChartData };

const props = defineProps<
    DashboardData & {
        filters: { company_id: number | null; branch_id: number | null; months: number };
        filterOptions: {
            companies: { id: number; name: string }[];
            branches: { id: number; name: string; company_id: number }[];
        };
    }
>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const companyFilter = ref(props.filters.company_id ? String(props.filters.company_id) : 'all');
const branchFilter = ref(props.filters.branch_id ? String(props.filters.branch_id) : 'all');
const monthsFilter = ref(String(props.filters.months));

const companyComboOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.filterOptions.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);
const branchComboOptions = computed(() => {
    const branches =
        companyFilter.value === 'all'
            ? props.filterOptions.branches
            : props.filterOptions.branches.filter((b) => String(b.company_id) === companyFilter.value);

    return [{ value: 'all', label: 'Todas las sucursales' }, ...branches.map((b) => ({ value: String(b.id), label: b.name }))];
});

// Encadenamiento en ambos sentidos: elegir empresa filtra las sucursales
// disponibles; elegir una sucursal directamente autoselecciona su empresa.
function onCompanyChange(value: string | number | null) {
    companyFilter.value = String(value ?? 'all');
    branchFilter.value = 'all';
}

function onBranchChange(value: string | number | null) {
    branchFilter.value = String(value ?? 'all');

    if (branchFilter.value !== 'all') {
        const branch = props.filterOptions.branches.find((b) => String(b.id) === branchFilter.value);

        if (branch) {
            companyFilter.value = String(branch.company_id);
        }
    }
}

// Panel en tiempo real: cada cambio de filtro vuelve a consultar el mismo
// endpoint que alimenta la carga inicial, sin recargar la página.
const stats = ref<Stats>(props.stats);
const charts = ref<ChartData>(props.charts);
const loading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

async function loadData() {
    loading.value = true;

    try {
        const data = await getJson<DashboardData>('/dashboard/datos', {
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
            branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
            months: monthsFilter.value,
        });
        stats.value = data.stats;
        charts.value = data.charts;
    } finally {
        loading.value = false;
    }
}

watch([companyFilter, branchFilter, monthsFilter], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadData, 300);
});

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
    <Head title="Inicio" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Inicio" description="Resumen del inventario de TI">
            <template #actions>
                <Combobox
                    :model-value="companyFilter"
                    :options="companyComboOptions"
                    placeholder="Empresa"
                    search-placeholder="Buscar empresa..."
                    class="w-40"
                    @update:model-value="onCompanyChange"
                />
                <Combobox
                    :model-value="branchFilter"
                    :options="branchComboOptions"
                    placeholder="Sucursal"
                    search-placeholder="Buscar sucursal..."
                    class="w-40"
                    @update:model-value="onBranchChange"
                />
                <Select v-model="monthsFilter">
                    <SelectTrigger class="w-36"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="3">Últimos 3 meses</SelectItem>
                        <SelectItem value="6">Últimos 6 meses</SelectItem>
                        <SelectItem value="12">Últimos 12 meses</SelectItem>
                    </SelectContent>
                </Select>
            </template>
        </PageHeader>

        <!-- Mobile quick actions -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:hidden">
            <Link href="/escanear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]">
                <QrCode class="size-6 text-primary" />
                <span class="text-xs font-medium">Escanear QR</span>
            </Link>
            <Link href="/activos/crear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]">
                <Plus class="size-6 text-primary" />
                <span class="text-xs font-medium">Nuevo activo</span>
            </Link>
            <Link href="/activos" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]">
                <Search class="size-6 text-primary" />
                <span class="text-xs font-medium">Buscar activo</span>
            </Link>
            <Link href="/auditorias/crear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]">
                <ClipboardCheck class="size-6 text-primary" />
                <span class="text-xs font-medium">Nueva auditoría</span>
            </Link>
        </div>

        <div class="flex items-center gap-2">
            <span class="relative flex size-2.5">
                <span
                    class="absolute inline-flex size-full animate-ping rounded-full bg-primary opacity-75"
                    :class="{ 'opacity-0': !loading }"
                />
                <span class="relative inline-flex size-2.5 rounded-full bg-primary" />
            </span>
            <p class="text-xs text-muted-foreground">{{ loading ? 'Actualizando...' : 'Datos en tiempo real' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 transition-opacity duration-200 sm:grid-cols-4" :class="{ 'opacity-60': loading }">
            <StatCard title="Total de activos" :value="stats.total" :icon="Boxes" />
            <StatCard title="En inventario" :value="stats.inInventory" :icon="PackageCheck" tone="positive" />
            <StatCard title="Dados de baja" :value="stats.decommissioned" :icon="Trash2" />
            <StatCard title="Dañados" :value="stats.damaged" :icon="Wrench" tone="warning" />
            <StatCard title="En revisión" :value="stats.inReview" :icon="ShieldAlert" tone="warning" />
            <StatCard title="Préstamos activos" :value="stats.activeLoans" :icon="UserRoundX" />
            <StatCard title="Préstamos vencidos" :value="stats.overdueLoans" :icon="UserRoundX" tone="destructive" />
            <StatCard title="Piezas disponibles" :value="stats.availableParts" :icon="Wrench" tone="positive" />
        </div>

        <div class="grid gap-4 transition-opacity duration-200 lg:grid-cols-2" :class="{ 'opacity-60': loading }">
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Activos por empresa</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byCompany.map((c) => ({ label: c.name, value: c.total }))" />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Activos por tipo</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart :data="charts.byType.map((t) => ({ label: t.name, value: t.total }))" />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Estado del inventario</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart
                        :data="charts.byStatus.map((s) => ({ label: s.label, value: s.total, colorVar: statusColorMap[s.color] ?? '--chart-3' }))"
                    />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Activos por sucursal</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byBranch.map((b) => ({ label: b.name, value: b.total }))" color-var="--chart-2" />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Activos por área</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byDepartment.map((d) => ({ label: d.name, value: d.total }))" color-var="--chart-3" />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Altas / bajas por mes</CardTitle></CardHeader>
                <CardContent>
                    <MonthlyTrendChart :data="charts.monthly" />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Préstamos por estado</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart
                        :data="charts.byLoanStatus.map((s) => ({ label: s.label, value: s.total, colorVar: statusColorMap[s.color] ?? '--chart-3' }))"
                    />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md">
                <CardHeader><CardTitle class="text-base">Piezas por estado</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart
                        :data="charts.byPartStatus.map((s) => ({ label: s.label, value: s.total, colorVar: statusColorMap[s.color] ?? '--chart-3' }))"
                    />
                </CardContent>
            </Card>
            <Card class="transition-shadow hover:shadow-md lg:col-span-2">
                <CardHeader><CardTitle class="text-base">Responsables con más activos asignados</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList
                        :data="charts.byResponsible.map((r) => ({ label: r.full_name, value: r.total }))"
                        color-var="--chart-4"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
