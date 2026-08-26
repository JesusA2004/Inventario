<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Image as ImageIcon, Info, ShieldCheck, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import Combobox from '@/components/Combobox.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
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
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';

type Settings = {
    legal_business_name: string | null;
    issuing_city: string | null;
    institutional_receiver_name: string | null;
    institutional_receiver_position: string | null;
    default_grantor_name: string | null;
    default_grantor_position: string | null;
    default_grantor_email: string | null;
    letterhead_path: string | null;
    header_logo_path: string | null;
    watermark_path: string | null;
    footer_path: string | null;
    active_responsibility_template_id: number | null;
    privacy_contact_email: string | null;
    privacy_notice_url: string | null;
    signing_link_valid_days: number;
    auto_generate_on_assignment: boolean;
};

type Template = {
    id: number;
    company_id: number | null;
    name: string;
    title: string;
    document_title: string | null;
    recipient_block: string | null;
    receiver_label: string | null;
    grantor_label: string | null;
    version: number;
    body: string;
    active: boolean;
    legal_review_status: 'draft' | 'approved';
};

type PrivacyNotice = {
    id: number;
    version: number;
    body: string;
    active: boolean;
};

const props = defineProps<{
    companies: { id: number; name: string }[];
    selectedCompanyId: number | null;
    settings: Settings | null;
    templates: Template[];
    privacyNotices: PrivacyNotice[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Configuración', href: '/configuracion' },
            {
                title: 'Documentos / Responsivas',
                href: '/configuracion/responsivas',
            },
        ],
    },
});

const companyId = ref(props.selectedCompanyId ?? '');

function switchCompany(value: string | number | null) {
    if (value === null) {
        return;
    }

    router.get(
        '/configuracion/responsivas',
        { company_id: value },
        { preserveState: false },
    );
}

// --- Datos generales + otorgante ---
const form = useForm({
    legal_business_name: props.settings?.legal_business_name ?? '',
    issuing_city: props.settings?.issuing_city ?? '',
    institutional_receiver_name:
        props.settings?.institutional_receiver_name ?? '',
    institutional_receiver_position:
        props.settings?.institutional_receiver_position ?? '',
    default_grantor_name: props.settings?.default_grantor_name ?? '',
    default_grantor_position: props.settings?.default_grantor_position ?? '',
    default_grantor_email: props.settings?.default_grantor_email ?? '',
    active_responsibility_template_id: props.settings
        ?.active_responsibility_template_id
        ? String(props.settings.active_responsibility_template_id)
        : '',
    privacy_contact_email: props.settings?.privacy_contact_email ?? '',
    privacy_notice_url: props.settings?.privacy_notice_url ?? '',
    signing_link_valid_days: props.settings?.signing_link_valid_days ?? 7,
    auto_generate_on_assignment:
        props.settings?.auto_generate_on_assignment ?? true,
});

function submit() {
    form.transform((data) => ({
        ...data,
        active_responsibility_template_id:
            data.active_responsibility_template_id || null,
        _method: 'patch',
    })).post(`/configuracion/responsivas/${companyId.value}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}

// --- Branding: 4 imágenes independientes ---
type BrandingKey = 'letterhead' | 'header_logo' | 'watermark' | 'footer';

const brandingLabels: Record<
    BrandingKey,
    { title: string; hint: string; column: keyof Settings }
> = {
    letterhead: {
        title: 'Membrete de página completa',
        hint: 'Imagen tamaño carta con logo + fondo + franja ya compuestos (como el oficial de MR LANA). Si se sube, tiene prioridad sobre las 3 piezas sueltas de abajo.',
        column: 'letterhead_path',
    },
    header_logo: {
        title: 'Logo (esquina superior izquierda)',
        hint: 'Se usa solo si no hay membrete completo configurado.',
        column: 'header_logo_path',
    },
    watermark: {
        title: 'Marca de agua / fondo',
        hint: 'Se muestra centrada y semitransparente detrás del texto.',
        column: 'watermark_path',
    },
    footer: {
        title: 'Franja / detalle inferior',
        hint: 'Tira decorativa al pie de cada página.',
        column: 'footer_path',
    },
};

const brandingForm = useForm({
    letterhead: null as File | null,
    header_logo: null as File | null,
    watermark: null as File | null,
    footer: null as File | null,
    remove_letterhead: false,
    remove_header_logo: false,
    remove_watermark: false,
    remove_footer: false,
});

function pickFile(key: BrandingKey, event: Event) {
    brandingForm[key] = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function removeExisting(key: BrandingKey) {
    brandingForm[key] = null;
    brandingForm[`remove_${key}` as const] = true;
    submitBranding();
}

function submitBranding() {
    brandingForm.post(`/configuracion/responsivas/${companyId.value}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            brandingForm.reset();
            toast.success('Branding actualizado.');
        },
    });
}

// --- Plantillas legales ---
const templateForm = useForm({
    name: 'default',
    title: '',
    document_title: '',
    recipient_block: '',
    receiver_label: '',
    grantor_label: '',
    body: '',
});
const showTemplateForm = ref(false);

const placeholderCheatsheet = [
    '{{ receiver_name }}',
    '{{ receiver_position }}',
    '{{ company_name }}',
    '{{ grantor_name }}',
    '{{ grantor_position }}',
    '{{ issued_city }}',
    '{{ issued_date_long }}',
    '{{ asset_name }}',
    '{{ asset_brand }}',
    '{{ asset_model }}',
    '{{ asset_condition }}',
    '{{ characteristics_table }}',
];

function submitTemplate() {
    templateForm
        .transform((data) => ({ ...data, company_id: companyId.value }))
        .post('/configuracion/responsivas/plantillas', {
            preserveScroll: true,
            onSuccess: () => {
                templateForm.reset();
                showTemplateForm.value = false;
            },
        });
}

function approveTemplate(template: Template) {
    router.post(
        `/configuracion/responsivas/plantillas/${template.id}/aprobar`,
        {},
        { preserveScroll: true },
    );
}

// --- Aviso de privacidad ---
const privacyForm = useForm({ body: '' });
const showPrivacyForm = ref(false);

function submitPrivacy() {
    privacyForm.post('/configuracion/responsivas/aviso-privacidad', {
        preserveScroll: true,
        onSuccess: () => {
            privacyForm.reset();
            showPrivacyForm.value = false;
            toast.success('Aviso de privacidad publicado.');
        },
    });
}

const selectedCompanyName = computed(
    () =>
        props.companies.find((c) => String(c.id) === String(companyId.value))
            ?.name ?? '',
);
</script>

<template>
    <Head title="Documentos / Responsivas" />

    <div class="flex w-full max-w-4xl flex-col gap-6">
        <PageHeader
            title="Documentos / Responsivas"
            description="Formato legal y branding de la responsiva, por empresa"
        />

        <div class="grid gap-2 sm:max-w-xs">
            <Label>Empresa</Label>
            <Combobox
                :model-value="String(companyId)"
                :options="
                    companies.map((c) => ({
                        value: String(c.id),
                        label: c.name,
                    }))
                "
                @update:model-value="switchCompany"
            />
        </div>

        <Tabs v-if="settings" default-value="general" class="w-full">
            <TabsList class="w-full justify-start overflow-x-auto">
                <TabsTrigger value="general">General</TabsTrigger>
                <TabsTrigger value="plantilla">Plantilla legal</TabsTrigger>
                <TabsTrigger value="branding">Branding</TabsTrigger>
                <TabsTrigger value="privacidad"
                    >Aviso de privacidad</TabsTrigger
                >
            </TabsList>

            <!-- General -->
            <TabsContent value="general" class="space-y-4">
                <form
                    class="space-y-6 rounded-xl border border-border bg-card p-5"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="legal-business-name"
                                >Razón social utilizada en responsivas</Label
                            >
                            <Input
                                id="legal-business-name"
                                v-model="form.legal_business_name"
                                placeholder="Se usa el nombre de la empresa si se deja vacío"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="issuing-city"
                                >Ciudad / estado de emisión</Label
                            >
                            <Input
                                id="issuing-city"
                                v-model="form.issuing_city"
                                placeholder="Ej. Cuernavaca, Morelos"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="institutional-receiver-name"
                                >Destinatario institucional (opcional)</Label
                            >
                            <Input
                                id="institutional-receiver-name"
                                v-model="form.institutional_receiver_name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="institutional-receiver-position"
                                >Puesto del destinatario institucional</Label
                            >
                            <Input
                                id="institutional-receiver-position"
                                v-model="form.institutional_receiver_position"
                            />
                        </div>
                    </div>

                    <div
                        class="grid gap-4 rounded-lg border border-border p-4 sm:grid-cols-2"
                    >
                        <div class="sm:col-span-2">
                            <p class="text-sm font-medium text-foreground">
                                Otorgante predeterminado
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Quien "entrega" el equipo en la responsiva (hoy
                                puede ser el encargado de sistemas; mañana otra
                                persona, sin tocar código).
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="grantor-name">Nombre</Label>
                            <Input
                                id="grantor-name"
                                v-model="form.default_grantor_name"
                            />
                            <InputError
                                :message="form.errors.default_grantor_name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="grantor-position">Puesto</Label>
                            <Input
                                id="grantor-position"
                                v-model="form.default_grantor_position"
                                placeholder="Ej. Encargado de sistemas"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="grantor-email">Correo</Label>
                            <Input
                                id="grantor-email"
                                v-model="form.default_grantor_email"
                                type="email"
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="signing-days"
                                >Días de vigencia del enlace de firma</Label
                            >
                            <Input
                                id="signing-days"
                                v-model.number="form.signing_link_valid_days"
                                type="number"
                                min="1"
                                max="90"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="template-select"
                                >Plantilla legal activa</Label
                            >
                            <Select
                                v-model="form.active_responsibility_template_id"
                            >
                                <SelectTrigger
                                    id="template-select"
                                    class="w-full"
                                    ><SelectValue
                                        placeholder="Automática (la más reciente aprobada)"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="template in templates"
                                        :key="template.id"
                                        :value="String(template.id)"
                                    >
                                        {{ template.title }} — v{{
                                            template.version
                                        }}
                                        ({{ template.legal_review_status }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div
                            class="flex items-center justify-between rounded-lg border border-border p-3 sm:col-span-2"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    Generar responsiva automáticamente al
                                    asignar
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Controla el valor por defecto del
                                    interruptor al asignar un responsable.
                                </p>
                            </div>
                            <Switch
                                v-model="form.auto_generate_on_assignment"
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="privacy-email"
                                >Correo para privacidad / ARCO</Label
                            >
                            <Input
                                id="privacy-email"
                                v-model="form.privacy_contact_email"
                                type="email"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="privacy-url"
                                >URL del aviso de privacidad</Label
                            >
                            <Input
                                id="privacy-url"
                                v-model="form.privacy_notice_url"
                                placeholder="/aviso-de-privacidad"
                            />
                        </div>
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        Guardar configuración
                    </Button>
                </form>
            </TabsContent>

            <!-- Plantilla legal -->
            <TabsContent value="plantilla" class="space-y-4">
                <div class="rounded-xl border border-border bg-card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-foreground">
                            Plantillas de texto legal —
                            {{ selectedCompanyName }}
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showTemplateForm = !showTemplateForm"
                            >Nueva versión</Button
                        >
                    </div>

                    <div
                        class="mb-4 flex gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400"
                    >
                        <Info class="mt-0.5 size-3.5 shrink-0" />
                        <p>
                            El texto se guarda y se imprime EXACTO, tal cual se
                            captura aquí. Solo se sustituyen los placeholders
                            reconocidos; cualquier otra palabra queda igual en
                            el documento final.
                        </p>
                    </div>

                    <form
                        v-if="showTemplateForm"
                        class="mb-4 space-y-3 rounded-lg border border-dashed border-border p-4"
                        @submit.prevent="submitTemplate"
                    >
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Nombre interno de la versión</Label>
                                <Input
                                    v-model="templateForm.title"
                                    placeholder="Ej. Responsiva de equipo — MR LANA"
                                />
                                <InputError
                                    :message="templateForm.errors.title"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Título del documento (encabezado)</Label>
                                <Input
                                    v-model="templateForm.document_title"
                                    placeholder="Ej. RESPONSIVA DE EQUIPO DE CÓMPUTO"
                                />
                                <InputError
                                    :message="
                                        templateForm.errors.document_title
                                    "
                                />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label>
                                Bloque de destinatario (tal cual va en el
                                documento)
                            </Label>
                            <Textarea
                                v-model="templateForm.recipient_block"
                                rows="3"
                                placeholder="A QUIEN CORRESPONDA&#10;PRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.&#10;PRESENTE."
                            />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Etiqueta de firma — receptor</Label>
                                <Input
                                    v-model="templateForm.receiver_label"
                                    placeholder="Receptor"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Etiqueta de firma — otorgante</Label>
                                <Input
                                    v-model="templateForm.grantor_label"
                                    placeholder="Otorgante"
                                />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label>Clausulado completo</Label>
                            <Textarea
                                v-model="templateForm.body"
                                rows="14"
                                class="font-mono text-xs"
                                placeholder="Clausulado completo, tal cual el documento real. Usa {{ characteristics_table }} donde va la tabla de características."
                            />
                            <InputError :message="templateForm.errors.body" />
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="token in placeholderCheatsheet"
                                    :key="token"
                                    variant="secondary"
                                    class="font-mono text-[10px]"
                                >
                                    {{ token }}
                                </Badge>
                            </div>
                        </div>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="templateForm.processing"
                        >
                            <Spinner
                                v-if="templateForm.processing"
                                class="mr-1"
                            />
                            Crear versión (queda como borrador)
                        </Button>
                    </form>

                    <div class="space-y-2">
                        <div
                            v-for="template in templates"
                            :key="template.id"
                            class="flex items-center justify-between rounded-lg border border-border p-3"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{ template.title }} — v{{
                                        template.version
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        template.company_id
                                            ? 'Específica de esta empresa'
                                            : 'Global (usada si la empresa no tiene una propia)'
                                    }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    :variant="
                                        template.legal_review_status ===
                                        'approved'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    <ShieldCheck
                                        v-if="
                                            template.legal_review_status ===
                                            'approved'
                                        "
                                        class="mr-1 size-3"
                                    />
                                    {{
                                        template.legal_review_status ===
                                        'approved'
                                            ? 'Aprobada'
                                            : 'Requiere validación jurídica'
                                    }}
                                </Badge>
                                <Button
                                    v-if="
                                        template.legal_review_status !==
                                        'approved'
                                    "
                                    size="sm"
                                    variant="outline"
                                    @click="approveTemplate(template)"
                                >
                                    Marcar como aprobada
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </TabsContent>

            <!-- Branding -->
            <TabsContent value="branding" class="space-y-4">
                <div
                    class="grid gap-4 rounded-xl border border-border bg-card p-5 sm:grid-cols-2"
                >
                    <div
                        v-for="(meta, key) in brandingLabels"
                        :key="key"
                        class="grid gap-2 rounded-lg border border-border p-4"
                    >
                        <p class="text-sm font-medium text-foreground">
                            {{ meta.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ meta.hint }}
                        </p>

                        <div
                            v-if="settings[meta.column]"
                            class="relative mt-1 flex h-24 items-center justify-center overflow-hidden rounded border border-border bg-muted/30"
                        >
                            <img
                                :src="`/storage/${settings[meta.column]}`"
                                class="max-h-full max-w-full object-contain"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                class="absolute top-1 right-1 size-6"
                                title="Quitar"
                                @click="removeExisting(key)"
                            >
                                <X class="size-3.5" />
                            </Button>
                        </div>
                        <div
                            v-else
                            class="mt-1 flex h-24 items-center justify-center rounded border border-dashed border-border text-muted-foreground"
                        >
                            <ImageIcon class="size-6" />
                        </div>

                        <Input
                            type="file"
                            accept="image/*"
                            @change="pickFile(key, $event)"
                        />
                        <InputError :message="brandingForm.errors[key]" />
                    </div>
                </div>

                <p
                    v-if="
                        !settings.letterhead_path && !settings.header_logo_path
                    "
                    class="text-xs text-muted-foreground"
                >
                    Sin ninguna imagen configurada aquí, esta empresa usa el
                    membrete oficial de MR LANA por defecto si no es MR LANA
                    misma no verá branding en el PDF hasta que suba el suyo.
                </p>

                <Button
                    :disabled="
                        brandingForm.processing ||
                        !(
                            brandingForm.letterhead ||
                            brandingForm.header_logo ||
                            brandingForm.watermark ||
                            brandingForm.footer
                        )
                    "
                    @click="submitBranding"
                >
                    <Spinner v-if="brandingForm.processing" class="mr-1" />
                    Guardar branding
                </Button>
            </TabsContent>

            <!-- Aviso de privacidad -->
            <TabsContent value="privacidad" class="space-y-4">
                <div class="rounded-xl border border-border bg-card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-foreground">
                            Aviso de privacidad
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showPrivacyForm = !showPrivacyForm"
                            >Nueva versión</Button
                        >
                    </div>

                    <form
                        v-if="showPrivacyForm"
                        class="mb-4 space-y-3 rounded-lg border border-dashed border-border p-3"
                        @submit.prevent="submitPrivacy"
                    >
                        <Textarea
                            v-model="privacyForm.body"
                            rows="10"
                            placeholder="Contenido completo del aviso de privacidad..."
                        />
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="privacyForm.processing"
                        >
                            <Spinner
                                v-if="privacyForm.processing"
                                class="mr-1"
                            />
                            Publicar nueva versión
                        </Button>
                    </form>

                    <div class="space-y-2">
                        <div
                            v-for="notice in privacyNotices"
                            :key="notice.id"
                            class="flex items-center justify-between rounded-lg border border-border p-3"
                        >
                            <p class="text-sm font-medium">
                                Versión {{ notice.version }}
                            </p>
                            <Badge
                                :variant="
                                    notice.active ? 'default' : 'secondary'
                                "
                                >{{
                                    notice.active ? 'Vigente' : 'Histórica'
                                }}</Badge
                            >
                        </div>
                    </div>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
