<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImageOff, Search, X } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import Combobox from '@/components/Combobox.vue';
import DatePicker from '@/components/DatePicker.vue';
import HelpTip from '@/components/HelpTip.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { getJson } from '@/lib/http';

type AssetOption = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    company_id: number;
    asset_type?: string | null;
    photo_url?: string | null;
};
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

// Sin escribir nada se muestra un listado inicial de activos disponibles
// (en vez de dejar el buscador vacío hasta que el usuario teclee), para que
// se pueda elegir el equipo navegando en tarjetas con foto.
async function loadResults(query: string) {
    searching.value = true;

    try {
        results.value = await getJson<AssetOption[]>('/activos/buscar', { q: query || undefined, in_inventory_only: 1 });
    } finally {
        searching.value = false;
    }
}

watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadResults(value), 300);
});

onMounted(() => {
    if (!selectedAsset.value) {
        loadResults('');
    }
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
    loadResults(search.value);
}

if (props.preselectedAsset) {
    loadResponsibles(props.preselectedAsset.company_id);
}

const form = useForm({
    asset_id: undefined as number | undefined,
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

    form.asset_id = selectedAsset.value.id;
    form.transform((data) => ({ ...data, origin: 'index' })).post('/prestamos');
}
</script>

<template>
    <Head title="Nuevo préstamo" />

    <div class="flex w-full max-w-6xl flex-col gap-6">
        <PageHeader
            title="Nuevo préstamo"
            description="Registra la salida temporal de un equipo"
            help-text="1) Busca y elige el activo. 2) Indica a quién se asigna. 3) Captura la fecha de salida (hoy o una fecha pasada) y, si ya sabes cuándo regresa, la fecha de devolución esperada: siempre debe ser igual o posterior a la fecha de salida."
        />

        <div class="space-y-2">
            <Label>Activo</Label>
            <p v-if="!selectedAsset" class="text-xs text-muted-foreground">
                Busca y selecciona primero el equipo que se va a prestar: con eso el sistema carga los responsables de su empresa para elegir a quién se
                asigna, quién entrega y quién recibe.
            </p>
            <div v-if="selectedAsset" class="flex items-center justify-between rounded-lg border border-border bg-card p-3">
                <div>
                    <p class="font-mono text-sm font-medium">{{ selectedAsset.internal_code }}</p>
                    <p class="text-sm text-muted-foreground">{{ selectedAsset.name }}</p>
                </div>
                <Button type="button" variant="ghost" size="icon" @click="clearAsset">
                    <X class="size-4" />
                </Button>
            </div>
            <div v-else class="space-y-3">
                <div class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Buscar por clave o nombre del dispositivo..." class="pl-8" />
                </div>

                <p v-if="searching" class="text-xs text-muted-foreground">Buscando...</p>

                <div v-else-if="results.length === 0" class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    No hay activos disponibles que coincidan con la búsqueda.
                </div>

                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <button
                        v-for="asset in results"
                        :key="asset.id"
                        type="button"
                        class="flex flex-col overflow-hidden rounded-xl border border-border bg-card text-left shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md"
                        @click="selectAsset(asset)"
                    >
                        <div class="relative aspect-square w-full overflow-hidden bg-muted">
                            <img
                                v-if="asset.photo_url"
                                :src="asset.photo_url"
                                :alt="asset.name"
                                loading="lazy"
                                class="size-full object-cover"
                            />
                            <div v-else class="flex size-full flex-col items-center justify-center gap-1 text-muted-foreground/60">
                                <ImageOff class="size-6" />
                                <span class="text-[10px]">Sin foto</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-0.5 p-2.5">
                            <p class="truncate text-sm font-medium text-foreground">{{ asset.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">{{ asset.internal_code }}</p>
                            <p v-if="asset.asset_type" class="truncate text-xs text-muted-foreground">{{ asset.asset_type }}</p>
                        </div>
                    </button>
                </div>
            </div>
            <InputError :message="form.errors.asset_id" />
        </div>

        <template v-if="selectedAsset">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Asignado a</Label>
                    <Combobox
                        v-model="form.assigned_to_responsible_id"
                        :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Selecciona un responsable"
                    />
                    <InputError :message="form.errors.assigned_to_responsible_id" />
                </div>
                <div class="grid gap-2">
                    <Label>Entregó</Label>
                    <Combobox
                        v-model="form.delivered_by_responsible_id"
                        :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Opcional"
                    />
                    <InputError :message="form.errors.delivered_by_responsible_id" />
                </div>
                <div class="grid gap-2">
                    <Label>Recibió</Label>
                    <Combobox
                        v-model="form.received_by_responsible_id"
                        :options="responsibles.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Opcional"
                    />
                    <InputError :message="form.errors.received_by_responsible_id" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Fecha de salida</Label>
                    <DatePicker v-model="form.loan_date" from-today />
                    <InputError :message="form.errors.loan_date" />
                </div>
                <div class="grid gap-2">
                    <Label>
                        Devolución esperada (opcional)
                        <HelpTip text="Debe ser igual o posterior a la fecha de salida. Déjala vacía si todavía no sabes cuándo regresará el equipo." />
                    </Label>
                    <DatePicker v-model="form.expected_return_date" from-today />
                    <InputError :message="form.errors.expected_return_date" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="loan-reason">Motivo / observaciones</Label>
                <Textarea id="loan-reason" v-model="form.reason" rows="3" />
                <InputError :message="form.errors.reason" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        Confirmó quien entrega
                        <HelpTip text="Marca esto cuando la persona que entrega el equipo ya confirmó los datos del préstamo (a quién, con qué condiciones). Desactívalo si el registro se hizo sin su confirmación directa." />
                    </p>
                    <Switch v-model="form.delivered_confirmed" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        Confirmó quien recibe
                        <HelpTip text="Marca esto cuando la persona que recibe el equipo ya confirmó que lo está recibiendo en las condiciones descritas." />
                    </p>
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
