<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Combobox from '@/components/Combobox.vue';
import DatePicker from '@/components/DatePicker.vue';
import HelpTip from '@/components/HelpTip.vue';
import InputError from '@/components/InputError.vue';
import QuickCreateBrandDialog from '@/components/QuickCreateBrandDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { getJson } from '@/lib/http';
import type { PartFormData, PartFormOptions } from '@/types/parts';

type AssetOption = { id: number; public_id: string; internal_code: string; name: string };

const props = defineProps<{
    form: InertiaForm<PartFormData>;
    formOptions: PartFormOptions;
    initialRelatedAsset?: AssetOption | null;
}>();

const form = props.form;
const options = ref({ ...props.formOptions });

const companyOptions = computed(() => options.value.companies.map((c) => ({ value: c.id, label: c.name })));
const branchOptions = computed(() =>
    options.value.branches.filter((b) => String(b.company_id) === String(form.company_id)).map((b) => ({ value: b.id, label: b.name })),
);
const responsibleOptions = computed(() =>
    options.value.responsiblePeople.filter((r) => String(r.company_id) === String(form.company_id)).map((r) => ({ value: r.id, label: r.full_name })),
);

// Al cambiar de empresa, limpia sucursal/responsable que ya no correspondan.
watch(
    () => form.company_id,
    (newCompanyId) => {
        const branch = options.value.branches.find((b) => b.id === form.branch_id);

        if (branch && String(branch.company_id) !== String(newCompanyId)) {
            form.branch_id = '';
        }

        const responsible = options.value.responsiblePeople.find((r) => r.id === form.responsible_id);

        if (responsible && String(responsible.company_id) !== String(newCompanyId)) {
            form.responsible_id = '';
        }
    },
);

// Encadenamiento inverso: elegir la sucursal autoselecciona su empresa.
watch(
    () => form.branch_id,
    (newBranchId) => {
        const branch = options.value.branches.find((b) => b.id === newBranchId);

        if (branch && String(branch.company_id) !== String(form.company_id)) {
            form.company_id = branch.company_id;
        }
    },
);

const brandDialogOpen = ref(false);
function onBrandCreated(brand: { id: number; name: string }) {
    options.value.brands.push(brand);
    form.brand_id = brand.id;
}

// Related asset async search
const assetSearch = ref('');
const assetResults = ref<AssetOption[]>([]);
const selectedAsset = ref<AssetOption | null>(props.initialRelatedAsset ?? null);
let debounceTimer: ReturnType<typeof setTimeout>;

watch(assetSearch, (value) => {
    clearTimeout(debounceTimer);

    if (!value || value.length < 2) {
        assetResults.value = [];

        return;
    }

    debounceTimer = setTimeout(async () => {
        assetResults.value = await getJson<AssetOption[]>('/activos/buscar', { q: value });
    }, 300);
});

function selectAsset(asset: AssetOption) {
    selectedAsset.value = asset;
    form.related_asset_id = asset.id;
    assetResults.value = [];
    assetSearch.value = '';
}

function clearAsset() {
    selectedAsset.value = null;
    form.related_asset_id = '';
}
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Identificación</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="part-name">Pieza / componente</Label>
                    <Input id="part-name" v-model="form.name" placeholder="Ej. Memoria RAM 8GB" />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="part-code">
                        Clave interna
                        <HelpTip text="El identificador que TÚ le asignas a la pieza dentro de este sistema para llevar su control (ej. CML-PZ-001). Es distinto al número de parte y al número de serie del fabricante." />
                    </Label>
                    <Input id="part-code" v-model="form.internal_code" class="font-mono uppercase" placeholder="Ej. CML-PZ-001" />
                    <InputError :message="form.errors.internal_code" />
                </div>
                <div class="grid gap-2">
                    <Label>Marca</Label>
                    <div class="flex gap-2">
                        <Combobox v-model="form.brand_id" :options="options.brands.map((b) => ({ value: b.id, label: b.name }))" placeholder="Opcional" class="flex-1" />
                        <Button type="button" variant="outline" size="icon" title="Nueva marca" @click="brandDialogOpen = true">+</Button>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="part-number">
                        Número de parte
                        <HelpTip text="El código que el FABRICANTE le puso a este modelo de pieza (a veces impreso como 'P/N' en la etiqueta o la caja). Sirve para identificar el modelo exacto al buscar un repuesto igual, no es un número único por unidad." />
                    </Label>
                    <Input id="part-number" v-model="form.part_number" placeholder="Ej. KVR16N11-8 (opcional)" />
                </div>
                <div class="grid gap-2">
                    <Label for="part-serial">
                        Número de serie
                        <HelpTip text="El número único de ESTA unidad en particular, asignado por el fabricante (a veces impreso como 'S/N'). A diferencia del número de parte, dos piezas del mismo modelo nunca comparten número de serie." />
                    </Label>
                    <Input id="part-serial" v-model="form.serial_number" placeholder="Opcional" />
                </div>
                <div class="grid gap-2">
                    <Label for="part-quantity">Cantidad</Label>
                    <Input id="part-quantity" v-model.number="form.quantity" type="number" min="1" />
                    <InputError :message="form.errors.quantity" />
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Ubicación y vínculo</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Empresa (opcional)</Label>
                    <Combobox v-model="form.company_id" :options="companyOptions" placeholder="Sin empresa" />
                </div>
                <div class="grid gap-2">
                    <Label>Sucursal (opcional)</Label>
                    <Combobox
                        v-model="form.branch_id"
                        :options="branchOptions"
                        :placeholder="form.company_id ? 'Sin sucursal' : 'Primero selecciona una empresa'"
                        :disabled="!form.company_id"
                    />
                </div>
            </div>
            <div class="grid gap-2">
                <Label>
                    Activo relacionado (opcional)
                    <HelpTip text="Liga esta pieza al equipo del que forma parte o al que acompaña. Combinado con 'Ensamblada' (abajo), define si se muestra como componente integrado en la ficha de ese activo." />
                </Label>
                <div v-if="selectedAsset" class="flex items-center justify-between rounded-lg border border-border bg-card p-3">
                    <div>
                        <p class="font-mono text-sm font-medium">{{ selectedAsset.internal_code }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedAsset.name }}</p>
                    </div>
                    <Button type="button" variant="ghost" size="icon" @click="clearAsset"><X class="size-4" /></Button>
                </div>
                <div v-else class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="assetSearch" placeholder="Buscar activo por clave o nombre..." class="pl-8" />
                    <div v-if="assetResults.length > 0" class="absolute z-10 mt-1 w-full rounded-lg border border-border bg-popover p-1 shadow-md">
                        <button
                            v-for="asset in assetResults"
                            :key="asset.id"
                            type="button"
                            class="flex w-full flex-col items-start rounded-md px-3 py-2 text-left text-sm hover:bg-accent"
                            @click="selectAsset(asset)"
                        >
                            <span class="font-mono font-medium">{{ asset.internal_code }}</span>
                            <span class="text-muted-foreground">{{ asset.name }}</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid gap-2">
                <Label>Responsable (opcional)</Label>
                <Combobox
                    v-model="form.responsible_id"
                    :options="responsibleOptions"
                    :placeholder="form.company_id ? 'Sin responsable' : 'Primero selecciona una empresa'"
                    :disabled="!form.company_id"
                />
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Estado</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Estatus</Label>
                    <Select v-model="form.status">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in formOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Fecha de compra (opcional)</Label>
                    <DatePicker v-model="form.purchase_date" from-today />
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="text-sm font-medium">En inventario</p>
                    <Switch v-model="form.in_inventory" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        Ensamblada
                        <HelpTip text="Actívalo cuando la pieza forma parte física de un activo (ej. la RAM de una laptop). Vincúlala en 'Activo relacionado' para que aparezca en la pestaña Piezas de ese equipo." />
                    </p>
                    <Switch v-model="form.assembled" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="flex items-center gap-1.5 text-sm font-medium">
                        Requiere etiqueta QR
                        <HelpTip text="Actívalo solo si esta pieza necesita su propio código QR físico (por ejemplo, una refacción cara que se mueve mucho). La mayoría de las piezas ensambladas no lo necesitan." />
                    </p>
                    <Switch v-model="form.needs_label" />
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Información adicional</h2>
            <div class="grid gap-2">
                <Label for="part-specs">Especificaciones</Label>
                <Textarea id="part-specs" v-model="form.specifications" rows="2" />
            </div>
            <div class="grid gap-2">
                <Label for="part-notes">Comentarios</Label>
                <Textarea id="part-notes" v-model="form.notes" rows="2" />
            </div>
            <div class="grid gap-2">
                <Label for="part-invoice">Factura (URL, opcional)</Label>
                <Input id="part-invoice" v-model="form.invoice_url" placeholder="https://..." />
                <InputError :message="form.errors.invoice_url" />
            </div>
        </section>

        <QuickCreateBrandDialog v-model:open="brandDialogOpen" @created="onBrandCreated" />
    </div>
</template>
