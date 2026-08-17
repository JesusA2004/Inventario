<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Combobox from '@/components/Combobox.vue';
import HelpTip from '@/components/HelpTip.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
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

type Settings = {
    system_name: string;
    logo_path: string | null;
    qr_base_url: string | null;
    internal_code_format: string;
    label_template: string;
    default_company_id: number | null;
};

const props = defineProps<{
    settings: Settings;
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Configuración', href: '/configuracion' }] },
});

const form = useForm({
    system_name: props.settings.system_name,
    logo: null as File | null,
    qr_base_url: props.settings.qr_base_url ?? '',
    internal_code_format: props.settings.internal_code_format,
    label_template: props.settings.label_template,
    default_company_id: props.settings.default_company_id ?? '',
});

function onLogoChange(event: Event) {
    form.logo = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post('/configuracion', { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Configuración" />

    <div class="flex w-full max-w-5xl flex-col gap-6">
        <PageHeader title="Configuración" description="Preferencias generales del sistema" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="system-name">Nombre del sistema</Label>
                    <Input id="system-name" v-model="form.system_name" />
                    <InputError :message="form.errors.system_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="logo">Logo</Label>
                    <img v-if="settings.logo_path" :src="`/storage/${settings.logo_path}`" class="mb-2 h-12 rounded border border-border object-contain" />
                    <Input id="logo" type="file" accept="image/*" @change="onLogoChange" />
                    <InputError :message="form.errors.logo" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="qr-domain" class="flex items-center gap-1.5">
                        Dominio base para códigos QR
                        <HelpTip text="El enlace que queda guardado dentro de cada QR impreso empieza con este dominio. Solo cámbialo si vas a publicar el sistema en un dominio propio; de lo contrario, no lo toques." />
                    </Label>
                    <Input id="qr-domain" v-model="form.qr_base_url" placeholder="https://inventario.mr-lana.com" />
                    <p class="text-xs text-muted-foreground">Déjalo vacío para usar automáticamente la URL de la aplicación.</p>
                    <InputError :message="form.errors.qr_base_url" />
                </div>

                <div class="grid gap-2">
                    <Label for="code-format">Formato de clave interna</Label>
                    <Input id="code-format" v-model="form.internal_code_format" />
                    <p class="text-xs text-muted-foreground">Referencia informativa: {empresa}-{tipo}-{consecutivo}.</p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label class="flex items-center gap-1.5">
                        Plantilla de etiquetas predeterminada
                        <HelpTip text="Solo define el número de columnas por hoja al imprimir etiquetas en lote. El tamaño de cada etiqueta (pequeño, mediano, grande) se elige aparte, en el diálogo de Etiquetas QR." />
                    </Label>
                    <Select v-model="form.label_template">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="standard">Estándar (2 columnas)</SelectItem>
                            <SelectItem value="compact">Compacta (3 columnas)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-2">
                    <Label>Empresa predeterminada (opcional)</Label>
                    <Combobox
                        v-model="form.default_company_id"
                        :options="companies.map((c) => ({ value: c.id, label: c.name }))"
                        placeholder="Ninguna"
                    />
                </div>
            </div>

            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" class="mr-1" />
                Guardar configuración
            </Button>
        </form>
    </div>
</template>
