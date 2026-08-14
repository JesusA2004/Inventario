<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import { ref } from 'vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import HorizontalBarList from '@/components/charts/HorizontalBarList.vue';
import MonthlyTrendChart from '@/components/charts/MonthlyTrendChart.vue';
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

const props = defineProps<{
    stats: Stats;
    charts: {
        byCompany: { name: string; total: number }[];
        byType: { name: string; total: number }[];
        byStatus: { label: string; color: string; total: number }[];
        byBranch: { name: string; total: number }[];
        byDepartment: { name: string; total: number }[];
        monthly: { month: string; altas: number; bajas: number }[];
    };
    filters: { company_id: number | null; branch_id: number | null; months: number };
    filterOptions: {
        companies: { id: number; name: string }[];
        branches: { id: number; name: string; company_id: number }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const companyFilter = ref(props.filters.company_id ? String(props.filters.company_id) : 'all');
const branchFilter = ref(props.filters.branch_id ? String(props.filters.branch_id) : 'all');
const monthsFilter = ref(String(props.filters.months));

function applyFilters() {
    router.get(
        '/dashboard',
        {
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
            branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
            months: monthsFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

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
                <Select v-model="companyFilter" @update:model-value="applyFilters">
                    <SelectTrigger class="w-40"><SelectValue placeholder="Empresa" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas las empresas</SelectItem>
                        <SelectItem v-for="c in filterOptions.companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="monthsFilter" @update:model-value="applyFilters">
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
            <Link href="/escanear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm">
                <QrCode class="size-6 text-primary" />
                <span class="text-xs font-medium">Escanear QR</span>
            </Link>
            <Link href="/activos/crear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm">
                <Plus class="size-6 text-primary" />
                <span class="text-xs font-medium">Nuevo activo</span>
            </Link>
            <Link href="/activos" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm">
                <Search class="size-6 text-primary" />
                <span class="text-xs font-medium">Buscar activo</span>
            </Link>
            <Link href="/auditorias/crear" class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card p-4 text-center shadow-sm">
                <ClipboardCheck class="size-6 text-primary" />
                <span class="text-xs font-medium">Nueva auditoría</span>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <StatCard title="Total de activos" :value="stats.total" :icon="Boxes" />
            <StatCard title="En inventario" :value="stats.inInventory" :icon="PackageCheck" tone="positive" />
            <StatCard title="Dados de baja" :value="stats.decommissioned" :icon="Trash2" />
            <StatCard title="Dañados" :value="stats.damaged" :icon="Wrench" tone="warning" />
            <StatCard title="En revisión" :value="stats.inReview" :icon="ShieldAlert" tone="warning" />
            <StatCard title="Préstamos activos" :value="stats.activeLoans" :icon="UserRoundX" />
            <StatCard title="Préstamos vencidos" :value="stats.overdueLoans" :icon="UserRoundX" tone="destructive" />
            <StatCard title="Piezas disponibles" :value="stats.availableParts" :icon="Wrench" tone="positive" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle class="text-base">Activos por empresa</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byCompany.map((c) => ({ label: c.name, value: c.total }))" />
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle class="text-base">Activos por tipo</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart :data="charts.byType.map((t) => ({ label: t.name, value: t.total }))" />
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle class="text-base">Estado del inventario</CardTitle></CardHeader>
                <CardContent>
                    <DonutChart
                        :data="charts.byStatus.map((s) => ({ label: s.label, value: s.total, colorVar: statusColorMap[s.color] ?? '--chart-3' }))"
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle class="text-base">Activos por sucursal</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byBranch.map((b) => ({ label: b.name, value: b.total }))" color-var="--chart-2" />
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle class="text-base">Activos por área</CardTitle></CardHeader>
                <CardContent>
                    <HorizontalBarList :data="charts.byDepartment.map((d) => ({ label: d.name, value: d.total }))" color-var="--chart-3" />
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle class="text-base">Altas / bajas por mes</CardTitle></CardHeader>
                <CardContent>
                    <MonthlyTrendChart :data="charts.monthly" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
