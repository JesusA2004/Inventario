<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import Combobox from '@/components/Combobox.vue';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { getJson } from '@/lib/http';

type AssetOption = { id: number; public_id: string; internal_code: string; name: string; company_id: number };
type ResponsibleOption = { id: number; full_name: string };

const props = defineProps<{
    preselectedAsset: AssetOption | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Préstamos', href: '/prestamos' },
            { title: 'Nuevo préstamo', href: '/prestamos/crear' },
        ],
    },
});

const search = ref('');
const results = ref<AssetOption[]>([]);
const searching = ref(false);
const selectedAsset = ref<AssetOption | null>(props.preselectedAsset);
const responsibles = ref<ResponsibleOption[]>([]);
let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(debounceTimer);

    if (!value || value.length < 2) {
        results.value = [];

        return;
    }

    debounceTimer = setTimeout(async () => {
        searching.value = true;
        results.value = await getJson<AssetOption[]>('/activos/buscar', { q: value, in_inventory_only: 1 });
        searching.value = false;
    }, 300);
});

async function loadResponsibles(companyId: number) {
    responsibles.value = await getJson<ResponsibleOption[]>(`/prestamos/empresas/${companyId}/responsables`);
}

function selectAsset(asset: AssetOption) {
    selectedAsset.value = asset;
    results.value = [];
    search.value = '';
    loadResponsibles(asset.company_id);
}

function clearAsset() {
    selectedAsset.value = null;
    responsibles.value = [];
}

if (props.preselectedAsset) {
    loadResponsibles(props.preselectedAsset.company_id);
}

const form = useForm({
    assigned_to_responsible_id: '' as number | string,
    delivered_by_responsible_id: '' as number | string,
    received_by_responsible_id: '' as number | string,
    reason: '',
    loan_date: new Date().toISOString().slice(0, 10),
    expected_return_date: null as string | null,
    delivered_confirmed: true,
    received_confirmed: true,
});

function submit() {
    if (!selectedAsset.value) {
return;
}

    form.transform((data) => ({ ...data, asset_id: selectedAsset.value?.id, origin: 'index' })).post('/prestamos');
}
</script>

<template>
    <Head title="Nuevo préstamo" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <PageHeader title="Nuevo préstamo" description="Registra la salida temporal de un equipo" />

        <div class="space-y-2">
            <Label>Activo</Label>
            <div v-if="selectedAsset" class="flex items-center justify-between rounded-lg border border-border bg-card p-3">
                <div>
                    <p class="font-mono text-sm font-medium">{{ selectedAsset.internal_code }}</p>
                    <p class="text-sm text-muted-foreground">{{ selectedAsset.name }}</p>
                </div>
                <Button type="button" variant="ghost" size="icon" @click="clearAsset">
                    <X class="size-4" />
                </Button>
            </div>
            <div v-else class="relative">
                <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Buscar por clave o nombre del dispositivo..." class="pl-8" />
                <div
                    v-if="results.length > 0"
                    class="absolute z-10 mt-1 w-full rounded-lg border border-border bg-popover p-1 shadow-md"
                >
                    <button
                        v-for="asset in results"
                        :key="asset.id"
                        type="button"
                        class="flex w-full flex-col items-start rounded-md px-3 py-2 text-left text-sm hover:bg-accent"
                        @click="selectAsset(asset)"
                    >
                        <span class="font-mono font-medium">{{ asset.internal_code }}</span>
                        <span class="text-muted-foreground">{{ asset.name }}</span>
                    </button>
                </div>
                <p v-if="searching" class="mt-1 text-xs text-muted-foreground">Buscando...</p>
            </div>
            <InputError :message="form.errors.asset_id" />
        </div>

        <template v-if="selectedAsset">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Asignado a</Label>
                    <Combobox
                        v-model="form.assigned_to_responsible_id"
                        :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Selecciona un responsable"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Entregó</Label>
                    <Combobox
                        v-model="form.delivered_by_responsible_id"
                        :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Opcional"
                    />
                </div>
            </div>
            <div class="grid gap-2">
                <Label>Recibió</Label>
                <Combobox
                    v-model="form.received_by_responsible_id"
                    :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                    placeholder="Opcional"
                />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Fecha de salida</Label>
                    <DatePicker v-model="form.loan_date" from-today />
                    <InputError :message="form.errors.loan_date" />
                </div>
                <div class="grid gap-2">
                    <Label>Devolución esperada (opcional)</Label>
                    <DatePicker v-model="form.expected_return_date" from-today />
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="loan-reason">Motivo / observaciones</Label>
                <Textarea id="loan-reason" v-model="form.reason" rows="3" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="text-sm font-medium">Confirmó quien entrega</p>
                    <Switch v-model="form.delivered_confirmed" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="text-sm font-medium">Confirmó quien recibe</p>
                    <Switch v-model="form.received_confirmed" />
                </div>
            </div>

            <Button type="button" :disabled="form.processing" @click="submit">
                <Spinner v-if="form.processing" class="mr-1" />
                Registrar préstamo
            </Button>
        </template>
    </div>
</template>
