<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Download,
    FileText,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SignaturePad from '@/components/responsibilities/SignaturePad.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';

type PublicResponsibility = {
    document_number: string | null;
    document_title: string;
    asset_name: string | null;
    internal_code: string | null;
    receiver_name: string;
    signed_at?: string | null;
};

const props = defineProps<{
    state: 'ok' | 'expired' | 'already_signed' | 'signed';
    token?: string;
    responsibility?: PublicResponsibility;
    pdfPreviewUrl?: string;
    privacyNoticeUrl?: string;
    downloadUrl?: string;
}>();

const step = ref(1);
const hasSignature = ref(false);
const signaturePad = ref<InstanceType<typeof SignaturePad> | null>(null);

const form = useForm({
    token: props.token ?? '',
    consent_accepted: false,
    privacy_accepted: false,
    signature: '',
});

const canContinueToSign = computed(
    () => form.consent_accepted && form.privacy_accepted,
);

function clearSignature() {
    signaturePad.value?.clear();
}

function submit() {
    const dataUrl = signaturePad.value?.toDataUrl();

    if (!dataUrl) {
        return;
    }

    form.signature = dataUrl;
    form.post(window.location.pathname, { preserveScroll: true });
}
</script>

<template>
    <Head title="Firmar responsiva" />

    <div class="min-h-dvh bg-[hsl(217_40%_13%)] px-4 py-8">
        <div class="mx-auto flex max-w-md flex-col gap-4">
            <div class="flex flex-col items-center gap-2 pb-2 text-center">
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-[hsl(206_80%_55%)]"
                >
                    <AppLogoIcon
                        class="size-5 fill-current text-[hsl(217_40%_12%)]"
                    />
                </span>
                <p class="text-sm font-medium text-white/70">
                    MR LANA · Responsiva de equipo
                </p>
            </div>

            <!-- Estado: expirado -->
            <div
                v-if="state === 'expired'"
                class="overflow-hidden rounded-2xl border border-white/10 bg-white p-6 text-center shadow-xl"
            >
                <Clock class="mx-auto mb-3 size-10 text-amber-500" />
                <h1 class="text-lg font-semibold text-slate-900">
                    Este enlace ya venció
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Por seguridad, los enlaces de firma tienen una vigencia
                    limitada. Solicita al área de Sistemas que te reenvíe la
                    invitación.
                </p>
            </div>

            <!-- Estado: ya no acepta firma (cancelada / firma física ya cargada) -->
            <div
                v-else-if="state === 'already_signed'"
                class="overflow-hidden rounded-2xl border border-white/10 bg-white p-6 text-center shadow-xl"
            >
                <AlertTriangle class="mx-auto mb-3 size-10 text-slate-400" />
                <h1 class="text-lg font-semibold text-slate-900">
                    Este documento ya no acepta firma
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Comunícate con el área de Sistemas si crees que esto es un
                    error.
                </p>
            </div>

            <!-- Estado: firmada -->
            <div
                v-else-if="state === 'signed'"
                class="overflow-hidden rounded-2xl border border-white/10 bg-white p-6 text-center shadow-xl"
            >
                <CheckCircle2 class="mx-auto mb-3 size-10 text-emerald-500" />
                <h1 class="text-lg font-semibold text-slate-900">
                    Responsiva firmada correctamente
                </h1>
                <div class="mt-3 space-y-1 text-sm text-slate-600">
                    <p v-if="responsibility?.asset_name">
                        Equipo:
                        <span class="font-medium text-slate-900">{{
                            responsibility.asset_name
                        }}</span>
                    </p>
                    <p v-if="responsibility?.signed_at">
                        Fecha:
                        <span class="font-medium text-slate-900">{{
                            responsibility.signed_at
                        }}</span>
                    </p>
                </div>
                <a
                    v-if="downloadUrl"
                    :href="downloadUrl"
                    class="mt-5 inline-block"
                >
                    <Button
                        ><Download class="mr-1 size-4" /> Descargar mi
                        copia</Button
                    >
                </a>
            </div>

            <!-- Flujo normal -->
            <template v-else>
                <div class="flex items-center justify-center gap-1.5">
                    <div
                        v-for="n in 3"
                        :key="n"
                        class="h-1.5 flex-1 rounded-full"
                        :class="
                            step >= n ? 'bg-[hsl(206_80%_55%)]' : 'bg-white/15'
                        "
                    />
                </div>
                <p class="text-center text-xs text-white/50">
                    Paso {{ step }} de 3 —
                    {{
                        step === 1
                            ? 'Revisar'
                            : step === 2
                              ? 'Aceptar'
                              : 'Firmar'
                    }}
                </p>

                <div
                    class="overflow-hidden rounded-2xl border border-white/10 bg-white shadow-xl"
                >
                    <div class="bg-[hsl(217_40%_13%)] px-6 py-5 text-center">
                        <p
                            class="text-xs font-semibold tracking-wide text-white/60 uppercase"
                        >
                            {{
                                responsibility?.document_title ??
                                'Responsiva de equipo'
                            }}
                        </p>
                        <p class="mt-1 text-lg font-bold text-white">
                            {{ responsibility?.asset_name }}
                        </p>
                        <p
                            v-if="responsibility?.internal_code"
                            class="mt-0.5 font-mono text-sm text-white/80"
                        >
                            {{ responsibility.internal_code }}
                        </p>
                    </div>

                    <!-- Paso 1: Revisar -->
                    <div v-if="step === 1" class="space-y-4 px-6 py-6">
                        <p class="text-sm text-slate-600">
                            Asignado a
                            <span class="font-medium text-slate-900">{{
                                responsibility?.receiver_name
                            }}</span>
                        </p>
                        <a
                            v-if="pdfPreviewUrl"
                            :href="pdfPreviewUrl"
                            target="_blank"
                            class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <FileText class="size-4" /> Ver documento completo
                        </a>
                        <Button class="w-full" @click="step = 2"
                            >Continuar</Button
                        >
                    </div>

                    <!-- Paso 2: Aceptar -->
                    <div v-else-if="step === 2" class="space-y-4 px-6 py-6">
                        <p class="text-sm font-medium text-slate-900">
                            Aviso importante
                        </p>
                        <label class="flex items-start gap-2.5">
                            <Checkbox
                                v-model="form.consent_accepted"
                                class="mt-0.5"
                            />
                            <span class="text-sm text-slate-600">
                                He leído la responsiva y manifiesto mi
                                conformidad con la recepción del equipo y con el
                                contenido del documento.
                            </span>
                        </label>
                        <label class="flex items-start gap-2.5">
                            <Checkbox
                                v-model="form.privacy_accepted"
                                class="mt-0.5"
                            />
                            <span class="text-sm text-slate-600">
                                He leído el
                                <a
                                    :href="privacyNoticeUrl"
                                    target="_blank"
                                    class="font-medium text-sky-700 underline"
                                    >aviso de privacidad</a
                                >
                                aplicable al tratamiento de mis datos y firma.
                            </span>
                        </label>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                class="flex-1"
                                @click="step = 1"
                                >Atrás</Button
                            >
                            <Button
                                class="flex-1"
                                :disabled="!canContinueToSign"
                                @click="step = 3"
                                >Continuar</Button
                            >
                        </div>
                    </div>

                    <!-- Paso 3: Firmar -->
                    <div v-else class="space-y-4 px-6 py-6">
                        <div>
                            <p class="text-xs text-slate-500">
                                Nombre completo
                            </p>
                            <p class="text-sm font-medium text-slate-900">
                                {{ responsibility?.receiver_name }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-1.5 text-xs text-slate-500">
                                Dibuja tu firma
                            </p>
                            <SignaturePad
                                ref="signaturePad"
                                v-model:has-signature="hasSignature"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" @click="clearSignature"
                                >Limpiar firma</Button
                            >
                            <Button
                                class="flex-1"
                                :disabled="!hasSignature || form.processing"
                                @click="submit"
                            >
                                <Spinner v-if="form.processing" class="mr-1" />
                                <ShieldCheck v-else class="mr-1 size-4" />
                                Firmar y aceptar
                            </Button>
                        </div>
                        <p
                            v-if="form.errors.signature"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.signature }}
                        </p>
                    </div>
                </div>
            </template>

            <p class="text-center text-xs text-white/30">
                MR LANA · Firma electrónica / evidencia electrónica de
                aceptación
            </p>
        </div>
    </div>
</template>
