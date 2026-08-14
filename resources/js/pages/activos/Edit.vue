<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { FileText, Image as ImageIcon, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import AssetForm from '@/components/assets/AssetForm.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { AssetFormData, AssetFormOptions } from '@/types/assets';

type AssetFile = {
    id: number;
    type: string;
    original_name: string;
    mime: string | null;
    path: string;
};

type Asset = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    company_id: number;
    branch_id: number;
    department_id: number | null;
    asset_type_id: number;
    brand_id: number | null;
    model: string | null;
    serial_number: string | null;
    status: string;
    in_inventory: boolean;
    current_responsible_id: number | null;
    delivered_by_responsible_id: number | null;
    components: string | null;
    specifications: string | null;
    notes: string | null;
    invoice_url: string | null;
    purchase_date: string | null;
    acquired_at: string | null;
    last_reviewed_at: string | null;
    files: AssetFile[];
};

const props = defineProps<{
    asset: Asset;
    formOptions: AssetFormOptions;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Activos', href: '/activos' },
        {
            title: props.asset.internal_code,
            href: `/activos/${props.asset.public_id}`,
        },
        {
            title: 'Editar',
            href: `/activos/${props.asset.public_id}/editar`,
        },
    ],
});

const form = useForm<AssetFormData>({
    company_id: props.asset.company_id,
    branch_id: props.asset.branch_id,
    department_id: props.asset.department_id ?? '',
    asset_type_id: props.asset.asset_type_id,
    name: props.asset.name,
    brand_id: props.asset.brand_id ?? '',
    model: props.asset.model ?? '',
    serial_number: props.asset.serial_number ?? '',
    internal_code: props.asset.internal_code,
    status: props.asset.status,
    in_inventory: props.asset.in_inventory,
    current_responsible_id: props.asset.current_responsible_id ?? '',
    delivered_by_responsible_id: props.asset.delivered_by_responsible_id ?? '',
    components: props.asset.components ?? '',
    specifications: props.asset.specifications ?? '',
    notes: props.asset.notes ?? '',
    invoice_url: props.asset.invoice_url ?? '',
    invoice_file: null,
    photos: [],
    purchase_date: props.asset.purchase_date,
    acquired_at: props.asset.acquired_at,
    last_reviewed_at: props.asset.last_reviewed_at,
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(
        `/activos/${props.asset.public_id}`,
        {
            forceFormData: true,
        },
    );
}

const confirmDeleteFile = ref<AssetFile | null>(null);
const deletingFile = ref(false);

function destroyFile() {
    if (!confirmDeleteFile.value) {
        return;
    }

    deletingFile.value = true;
    router.delete(
        `/activos/${props.asset.public_id}/archivos/${confirmDeleteFile.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deletingFile.value = false;
                confirmDeleteFile.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Editar ${asset.internal_code}`" />

    <div class="mx-auto flex max-w-6xl flex-col gap-6 pb-24">
        <PageHeader
            :title="`Editar ${asset.internal_code}`"
            :description="asset.name"
        />

        <div
            v-if="asset.files.length"
            class="rounded-xl border border-border bg-card p-4"
        >
            <p class="mb-3 text-sm font-semibold text-foreground">
                Archivos actuales
            </p>
            <ul class="space-y-2">
                <li
                    v-for="file in asset.files"
                    :key="file.id"
                    class="flex items-center justify-between gap-2 rounded-lg border border-border px-3 py-2 text-sm"
                >
                    <a
                        :href="`/storage/${file.path}`"
                        target="_blank"
                        class="flex items-center gap-2 truncate hover:underline"
                    >
                        <ImageIcon
                            v-if="file.type === 'foto'"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <FileText
                            v-else
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="truncate">{{ file.original_name }}</span>
                    </a>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0"
                        @click="confirmDeleteFile = file"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </li>
            </ul>
        </div>

        <AssetForm
            :form="form"
            :form-options="formOptions"
            mode="edit"
            :asset-id="asset.id"
        />
    </div>

    <div
        class="sticky bottom-0 -mx-4 flex flex-col gap-2 border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-t-xl sm:border sm:px-6"
    >
        <Button type="button" :disabled="form.processing" @click="submit">
            <Spinner v-if="form.processing" class="mr-1" />
            Guardar cambios
        </Button>
    </div>

    <ConfirmDialog
        :open="!!confirmDeleteFile"
        title="¿Eliminar este archivo?"
        description="Esta acción no se puede deshacer."
        confirm-text="Eliminar"
        destructive
        :loading="deletingFile"
        @update:open="(value) => !value && (confirmDeleteFile = null)"
        @confirm="destroyFile"
    />
</template>
