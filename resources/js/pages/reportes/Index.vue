<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, FileBarChart, FileText } from '@lucide/vue';
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

defineProps<{
    companies: { id: number; name: string }[];
    reports: { key: string; title: string }[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reportes', href: '/reportes' }] },
});

const companyFilter = ref('all');
const from = ref<string | null>(null);
const to = ref<string | null>(null);

const query = computed(() => {
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

    return params.toString();
});

function exportUrl(key: string, format: 'excel' | 'pdf'): string {
    return `/reportes/${key}/${format}${query.value ? `?${query.value}` : ''}`;
}

const descriptions: Record<string, string> = {
    inventario: 'Listado completo de activos con filtros por empresa, sucursal, área, responsable y fecha de alta.',
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
