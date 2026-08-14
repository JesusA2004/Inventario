<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { AlertTriangle, ChevronDown, Sparkles } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Combobox from '@/components/Combobox.vue';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import QuickCreateAssetTypeDialog from '@/components/QuickCreateAssetTypeDialog.vue';
import QuickCreateBranchDialog from '@/components/QuickCreateBranchDialog.vue';
import QuickCreateBrandDialog from '@/components/QuickCreateBrandDialog.vue';
import QuickCreateDepartmentDialog from '@/components/QuickCreateDepartmentDialog.vue';
import QuickCreateResponsibleDialog from '@/components/QuickCreateResponsibleDialog.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { getJson, postJson } from '@/lib/http';
import type { AssetFormData, AssetFormOptions } from '@/types/assets';

const props = defineProps<{
    form: InertiaForm<AssetFormData>;
    formOptions: AssetFormOptions;
    mode: 'create' | 'edit';
    assetId?: number;
}>();

const form = props.form;
const options = ref({ ...props.formOptions });

const companyOptions = computed(() =>
    options.value.companies.map((c) => ({
        value: c.id,
        label: `${c.name} (${c.code})`,
    })),
);
const branchOptions = computed(() =>
    options.value.branches
        .filter((b) => String(b.company_id) === String(form.company_id))
        .map((b) => ({ value: b.id, label: b.name })),
);
const departmentOptions = computed(() =>
    options.value.departments
        .filter(
            (d) =>
                !d.company_id ||
                String(d.company_id) === String(form.company_id),
        )
        .map((d) => ({ value: d.id, label: d.name })),
);
const assetTypeOptions = computed(() =>
    options.value.assetTypes.map((t) => ({ value: t.id, label: t.name })),
);
const brandOptions = computed(() =>
    options.value.brands.map((b) => ({ value: b.id, label: b.name })),
);
const responsibleOptions = computed(() =>
    options.value.responsiblePeople
        .filter((r) => String(r.company_id) === String(form.company_id))
        .map((r) => ({ value: r.id, label: r.full_name })),
);

// Al cambiar de empresa, limpia cualquier sucursal/área/responsable
// seleccionado que ya no pertenezca a la nueva empresa: nunca dejar un ID
// oculto de la empresa anterior.
watch(
    () => form.company_id,
    (newCompanyId) => {
        const branch = options.value.branches.find((b) => b.id === form.branch_id);

        if (branch && String(branch.company_id) !== String(newCompanyId)) {
            form.branch_id = '';
        }

        const department = options.value.departments.find((d) => d.id === form.department_id);

        if (department && department.company_id && String(department.company_id) !== String(newCompanyId)) {
            form.department_id = '';
        }

        const currentResponsible = options.value.responsiblePeople.find((r) => r.id === form.current_responsible_id);

        if (currentResponsible && String(currentResponsible.company_id) !== String(newCompanyId)) {
            form.current_responsible_id = '';
        }

        const deliveredBy = options.value.responsiblePeople.find((r) => r.id === form.delivered_by_responsible_id);

        if (deliveredBy && String(deliveredBy.company_id) !== String(newCompanyId)) {
            form.delivered_by_responsible_id = '';
        }
    },
);

// Quick-create dialogs
const branchDialogOpen = ref(false);
const departmentDialogOpen = ref(false);
const assetTypeDialogOpen = ref(false);
const brandDialogOpen = ref(false);
const responsibleDialogOpen = ref<false | 'current' | 'delivered'>(false);

function onBranchCreated(branch: {
    id: number;
    name: string;
    company_id: number;
}) {
    options.value.branches.push(branch);
    form.branch_id = branch.id;
}
function onDepartmentCreated(department: {
    id: number;
    name: string;
    company_id: number | null;
}) {
    options.value.departments.push(department);
    form.department_id = department.id;
}
function onAssetTypeCreated(assetType: {
    id: number;
    name: string;
    code: string;
}) {
    options.value.assetTypes.push(assetType);
    form.asset_type_id = assetType.id;
}
function onBrandCreated(brand: { id: number; name: string }) {
    options.value.brands.push(brand);
    form.brand_id = brand.id;
}
function onResponsibleCreated(responsible: {
    id: number;
    full_name: string;
    company_id: number;
}) {
    options.value.responsiblePeople.push({
        ...responsible,
        branch_id: null,
        department_id: null,
    });

    if (responsibleDialogOpen.value === 'current') {
        form.current_responsible_id = responsible.id;
    } else if (responsibleDialogOpen.value === 'delivered') {
        form.delivered_by_responsible_id = responsible.id;
    }
}

// Clave interna generation
const generatingCode = ref(false);
async function generateCode() {
    if (!form.company_id || !form.asset_type_id) {
        return;
    }

    generatingCode.value = true;

    try {
        const data = await postJson<{ code: string }>(
            '/activos/generar-clave',
            {
                company_id: form.company_id,
                asset_type_id: form.asset_type_id,
            },
        );
        form.internal_code = data.code;
    } finally {
        generatingCode.value = false;
    }
}

// Duplicate serial number check
const serialMatch = ref<{
    public_id: string;
    internal_code: string;
    name: string;
} | null>(null);
let serialTimeout: ReturnType<typeof setTimeout> | undefined;
watch(
    () => form.serial_number,
    (value) => {
        clearTimeout(serialTimeout);
        serialMatch.value = null;

        if (!value || value.length < 3) {
            return;
        }

        serialTimeout = setTimeout(async () => {
            const result = await getJson<{ match: typeof serialMatch.value }>(
                '/activos/verificar-serie',
                {
                    serial_number: value,
                    except: props.assetId,
                },
            );
            serialMatch.value = result.match;
        }, 400);
    },
);

const additionalOpen = ref(props.mode === 'edit');

const photoPreviewNames = computed(() =>
    form.photos?.map((f) => f.name).join(', '),
);

function onPhotosChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.photos = input.files ? Array.from(input.files) : [];
}

function onInvoiceFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.invoice_file = input.files?.[0] ?? null;
}
</script>

<template>
    <div class="space-y-8">
        <!-- Ubicación -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Ubicación</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Empresa</Label>
                    <Combobox
                        v-model="form.company_id"
                        :options="companyOptions"
                        placeholder="Selecciona una empresa"
                    />
                    <InputError :message="form.errors.company_id" />
                </div>
                <div class="grid gap-2">
                    <Label>Sucursal</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.branch_id"
                            :options="branchOptions"
                            placeholder="Selecciona una sucursal"
                            :disabled="!form.company_id"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="!form.company_id"
                            title="Nueva sucursal"
                            @click="branchDialogOpen = true"
                        >
                            +
                        </Button>
                    </div>
                    <InputError :message="form.errors.branch_id" />
                </div>
                <div class="grid gap-2">
                    <Label>Área / departamento</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.department_id"
                            :options="departmentOptions"
                            placeholder="Opcional"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Nueva área"
                            @click="departmentDialogOpen = true"
                        >
                            +
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Identificación -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">
                Identificación
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Tipo de activo</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.asset_type_id"
                            :options="assetTypeOptions"
                            placeholder="Selecciona un tipo"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Nuevo tipo"
                            @click="assetTypeDialogOpen = true"
                        >
                            +
                        </Button>
                    </div>
                    <InputError :message="form.errors.asset_type_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="asset-name">Nombre del dispositivo</Label>
                    <Input
                        id="asset-name"
                        v-model="form.name"
                        placeholder="Ej. Laptop Dell OptiPlex"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label>Marca</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.brand_id"
                            :options="brandOptions"
                            placeholder="Opcional"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Nueva marca"
                            @click="brandDialogOpen = true"
                        >
                            +
                        </Button>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="asset-model">Modelo</Label>
                    <Input
                        id="asset-model"
                        v-model="form.model"
                        placeholder="Opcional"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="asset-serial"
                        >Número de serie del fabricante</Label
                    >
                    <Input
                        id="asset-serial"
                        v-model="form.serial_number"
                        placeholder="Opcional"
                    />
                    <Alert
                        v-if="serialMatch"
                        variant="destructive"
                        class="mt-1"
                    >
                        <AlertTriangle class="size-4" />
                        <AlertTitle>Número de serie duplicado</AlertTitle>
                        <AlertDescription>
                            Ya existe un activo con este número de serie:
                            <a
                                :href="`/activos/${serialMatch.public_id}`"
                                target="_blank"
                                class="font-medium underline"
                            >
                                {{ serialMatch.internal_code }} ·
                                {{ serialMatch.name }}
                            </a>
                        </AlertDescription>
                    </Alert>
                </div>
                <div class="grid gap-2">
                    <Label for="asset-code">Clave interna</Label>
                    <div class="flex gap-2">
                        <Input
                            id="asset-code"
                            v-model="form.internal_code"
                            placeholder="Ej. CML-LAP-001"
                            class="flex-1 font-mono uppercase"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="
                                !form.company_id ||
                                !form.asset_type_id ||
                                generatingCode
                            "
                            @click="generateCode"
                        >
                            <Spinner v-if="generatingCode" class="mr-1" />
                            <Sparkles v-else class="mr-1 size-4" />
                            Generar
                        </Button>
                    </div>
                    <InputError :message="form.errors.internal_code" />
                </div>
            </div>
        </section>

        <!-- Asignación -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Asignación</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Responsable actual</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.current_responsible_id"
                            :options="responsibleOptions"
                            placeholder="Opcional (almacenado)"
                            :disabled="!form.company_id"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="!form.company_id"
                            title="Nuevo responsable"
                            @click="responsibleDialogOpen = 'current'"
                        >
                            +
                        </Button>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label>Entregó / responsable de revisión</Label>
                    <div class="flex gap-2">
                        <Combobox
                            v-model="form.delivered_by_responsible_id"
                            :options="responsibleOptions"
                            placeholder="Opcional"
                            :disabled="!form.company_id"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="!form.company_id"
                            title="Nuevo responsable"
                            @click="responsibleDialogOpen = 'delivered'"
                        >
                            +
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Estado -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Estado</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Estatus</Label>
                    <Select v-model="form.status">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in formOptions.statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">¿Sigue en inventario?</p>
                        <p class="text-xs text-muted-foreground">
                            Desactívalo solo si el equipo ya no está físicamente
                            en la empresa.
                        </p>
                    </div>
                    <Switch v-model="form.in_inventory" />
                </div>
            </div>
        </section>

        <!-- Fechas -->
        <section class="space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Fechas</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Fecha de alta</Label>
                    <DatePicker v-model="form.acquired_at" from-today />
                    <InputError :message="form.errors.acquired_at" />
                </div>
                <div class="grid gap-2">
                    <Label>Fecha de compra</Label>
                    <DatePicker
                        v-model="form.purchase_date"
                        placeholder="Opcional"
                        from-today
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Última revisión</Label>
                    <DatePicker
                        v-model="form.last_reviewed_at"
                        placeholder="Opcional"
                        from-today
                    />
                </div>
            </div>
        </section>

        <!-- Información adicional -->
        <Collapsible
            v-model:open="additionalOpen"
            class="rounded-xl border border-border"
        >
            <CollapsibleTrigger
                class="flex w-full items-center justify-between p-4 text-left"
            >
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        Información adicional
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Componentes, especificaciones, observaciones, factura y
                        fotografías
                    </p>
                </div>
                <ChevronDown
                    class="size-4 shrink-0 transition-transform"
                    :class="additionalOpen && 'rotate-180'"
                />
            </CollapsibleTrigger>
            <CollapsibleContent class="space-y-4 border-t border-border p-4">
                <div class="grid gap-2">
                    <Label for="asset-components">Componentes / incluye</Label>
                    <Textarea
                        id="asset-components"
                        v-model="form.components"
                        rows="2"
                        placeholder="Ej. Cargador, mochila, mouse"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="asset-specs">Especificaciones</Label>
                    <Textarea
                        id="asset-specs"
                        v-model="form.specifications"
                        rows="2"
                        placeholder="Ej. Core i5, 8GB RAM, 256GB SSD"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="asset-notes">Observaciones</Label>
                    <Textarea
                        id="asset-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="No captures contraseñas ni credenciales aquí"
                    />
                    <InputError :message="form.errors.notes" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="asset-invoice-url">Factura (URL)</Label>
                        <Input
                            id="asset-invoice-url"
                            v-model="form.invoice_url"
                            placeholder="https://..."
                        />
                        <InputError :message="form.errors.invoice_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="asset-invoice-file"
                            >Factura (archivo PDF o imagen)</Label
                        >
                        <Input
                            id="asset-invoice-file"
                            type="file"
                            accept=".pdf,image/*"
                            @change="onInvoiceFileChange"
                        />
                        <InputError :message="form.errors.invoice_file" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="asset-photos">Fotografías</Label>
                    <Input
                        id="asset-photos"
                        type="file"
                        accept="image/*"
                        multiple
                        capture="environment"
                        @change="onPhotosChange"
                    />
                    <p
                        v-if="photoPreviewNames"
                        class="text-xs text-muted-foreground"
                    >
                        {{ photoPreviewNames }}
                    </p>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <QuickCreateBranchDialog
            v-model:open="branchDialogOpen"
            :company-id="
                typeof form.company_id === 'number'
                    ? form.company_id
                    : Number(form.company_id) || null
            "
            @created="onBranchCreated"
        />
        <QuickCreateDepartmentDialog
            v-model:open="departmentDialogOpen"
            :company-id="
                typeof form.company_id === 'number'
                    ? form.company_id
                    : Number(form.company_id) || null
            "
            @created="onDepartmentCreated"
        />
        <QuickCreateAssetTypeDialog
            v-model:open="assetTypeDialogOpen"
            @created="onAssetTypeCreated"
        />
        <QuickCreateBrandDialog
            v-model:open="brandDialogOpen"
            @created="onBrandCreated"
        />
        <QuickCreateResponsibleDialog
            :open="!!responsibleDialogOpen"
            :company-id="
                typeof form.company_id === 'number'
                    ? form.company_id
                    : Number(form.company_id) || null
            "
            :branch-id="
                typeof form.branch_id === 'number'
                    ? form.branch_id
                    : Number(form.branch_id) || null
            "
            :department-id="
                typeof form.department_id === 'number'
                    ? form.department_id
                    : Number(form.department_id) || null
            "
            @update:open="
                (value) =>
                    (responsibleDialogOpen = value
                        ? responsibleDialogOpen
                        : false)
            "
            @created="onResponsibleCreated"
        />
    </div>
</template>
