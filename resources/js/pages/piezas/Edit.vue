<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import PartForm from '@/components/parts/PartForm.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { PartFormData, PartFormOptions } from '@/types/parts';

type Part = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    company_id: number | null;
    branch_id: number | null;
    related_asset_id: number | null;
    relatedAsset?: { id: number; public_id: string; internal_code: string; name: string } | null;
    brand_id: number | null;
    serial_number: string | null;
    part_number: string | null;
    status: string;
    in_inventory: boolean;
    quantity: number;
    specifications: string | null;
    assembled: boolean;
    notes: string | null;
    purchase_date: string | null;
    responsible_id: number | null;
    invoice_url: string | null;
    needs_label: boolean;
};

const props = defineProps<{
    part: Part;
    formOptions: PartFormOptions;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Piezas y refacciones', href: '/piezas' },
        { title: 'Editar pieza', href: `/piezas/${props.part.public_id}/editar` },
    ],
});

const form = useForm<PartFormData>({
    company_id: props.part.company_id ?? '',
    branch_id: props.part.branch_id ?? '',
    related_asset_id: props.part.related_asset_id ?? '',
    internal_code: props.part.internal_code,
    name: props.part.name,
    brand_id: props.part.brand_id ?? '',
    serial_number: props.part.serial_number ?? '',
    part_number: props.part.part_number ?? '',
    status: props.part.status,
    in_inventory: props.part.in_inventory,
    quantity: props.part.quantity,
    specifications: props.part.specifications ?? '',
    assembled: props.part.assembled,
    notes: props.part.notes ?? '',
    purchase_date: props.part.purchase_date,
    responsible_id: props.part.responsible_id ?? '',
    invoice_url: props.part.invoice_url ?? '',
    needs_label: props.part.needs_label,
});

function submit() {
    form.put(`/piezas/${props.part.public_id}`);
}
</script>

<template>
    <Head :title="`Editar ${part.internal_code}`" />

    <div class="mx-auto flex max-w-4xl flex-col gap-6 pb-24">
        <PageHeader :title="`Editar ${part.internal_code}`" :description="part.name" />
        <PartForm :form="form" :form-options="formOptions" :initial-related-asset="part.relatedAsset" />
    </div>

    <div class="sticky bottom-0 -mx-4 flex justify-end border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-t-xl sm:border sm:px-6">
        <Button type="button" :disabled="form.processing" @click="submit">
            <Spinner v-if="form.processing" class="mr-1" />
            Guardar cambios
        </Button>
    </div>
</template>
