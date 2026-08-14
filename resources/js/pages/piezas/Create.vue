<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import PartForm from '@/components/parts/PartForm.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { PartFormData, PartFormOptions } from '@/types/parts';

defineProps<{
    formOptions: PartFormOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Piezas y refacciones', href: '/piezas' },
            { title: 'Nueva pieza', href: '/piezas/crear' },
        ],
    },
});

const form = useForm<PartFormData>({
    company_id: '',
    branch_id: '',
    related_asset_id: '',
    internal_code: '',
    name: '',
    brand_id: '',
    serial_number: '',
    part_number: '',
    status: 'funcional',
    in_inventory: true,
    quantity: 1,
    specifications: '',
    assembled: false,
    notes: '',
    purchase_date: null,
    responsible_id: '',
    invoice_url: '',
    needs_label: false,
});

function submit() {
    form.post('/piezas');
}
</script>

<template>
    <Head title="Nueva pieza" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 pb-24">
        <PageHeader title="Nueva pieza" description="Registra una pieza o refacción en el inventario" />
        <PartForm :form="form" :form-options="formOptions" />
    </div>

    <div class="sticky bottom-0 -mx-4 flex justify-end border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-t-xl sm:border sm:px-6">
        <Button type="button" :disabled="form.processing" @click="submit">
            <Spinner v-if="form.processing" class="mr-1" />
            Guardar pieza
        </Button>
    </div>
</template>
