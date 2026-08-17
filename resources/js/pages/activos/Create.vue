<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AssetForm from '@/components/assets/AssetForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { AssetFormData, AssetFormOptions } from '@/types/assets';

const props = defineProps<{
    formOptions: AssetFormOptions;
    prefill: {
        company_id?: string;
        branch_id?: string;
        department_id?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Activos', href: '/activos' },
            { title: 'Nuevo activo', href: '/activos/crear' },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm<AssetFormData>({
    company_id: props.prefill.company_id ?? '',
    branch_id: props.prefill.branch_id ?? '',
    department_id: props.prefill.department_id ?? '',
    asset_type_id: '',
    name: '',
    brand_id: '',
    model: '',
    serial_number: '',
    internal_code: '',
    status: 'activo',
    in_inventory: true,
    current_responsible_id: '',
    delivered_by_responsible_id: '',
    components: '',
    specifications: '',
    notes: '',
    invoice_url: '',
    invoice_file: null,
    photos: [],
    purchase_date: null,
    acquired_at: today,
    last_reviewed_at: null,
});

function submit(createAnother: boolean) {
    form.transform((data) => ({ ...data, create_another: createAnother })).post(
        '/activos',
        {
            forceFormData: true,
        },
    );
}
</script>

<template>
    <Head title="Nuevo activo" />

    <div class="flex w-full flex-col gap-6 pb-24">
        <PageHeader
            title="Nuevo activo"
            description="Registra un dispositivo en el inventario"
        />

        <AssetForm :form="form" :form-options="formOptions" mode="create" />
    </div>

    <div
        class="sticky bottom-0 -mx-4 flex flex-col gap-2 border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-t-xl sm:border sm:px-6"
    >
        <Button
            type="button"
            variant="outline"
            :disabled="form.processing"
            @click="submit(true)"
            class="sm:order-1"
        >
            <Spinner v-if="form.processing" class="mr-1" />
            Guardar y crear otro
        </Button>
        <Button
            type="button"
            :disabled="form.processing"
            @click="submit(false)"
            class="sm:order-2"
        >
            <Spinner v-if="form.processing" class="mr-1" />
            Guardar activo
        </Button>
    </div>
</template>
