<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Camera, CheckCircle2, QrCode } from '@lucide/vue';
import QrScanner from 'qr-scanner';
import QrScannerWorkerPath from 'qr-scanner/qr-scanner-worker.min.js?url';
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { getJson, postJson } from '@/lib/http';

QrScanner.WORKER_PATH = QrScannerWorkerPath;

type Audit = { id: number; name: string };
type AssetInfo = {
    public_id: string;
    internal_code: string;
    name: string;
    type: string | null;
    branch: string | null;
    department: string | null;
    responsible: string | null;
};

const props = defineProps<{
    audit: Audit | null;
    activeAudits: Audit[];
}>();

const videoRef = useTemplateRef<HTMLVideoElement>('video');
let scanner: QrScanner | null = null;

const cameraError = ref<string | null>(null);
const scanning = ref(false);
const foundAsset = ref<AssetInfo | null>(null);
const notFoundCode = ref<string | null>(null);
const marking = ref(false);
const showIssueForm = ref(false);
const issueStatus = ref('ubicacion_incorrecta');
const issueComment = ref('');
const markedCount = ref(0);

function goToAudit(auditId: string) {
    window.location.href = `/escanear?audit_id=${auditId}`;
}

function extractPublicId(text: string): string | null {
    const match = text.match(/\/a\/([^/?#]+)/);

    if (match) {
return match[1];
}

    if (/^[a-zA-Z0-9]{20,30}$/.test(text.trim())) {
return text.trim();
}

    return null;
}

async function onDecoded(text: string) {
    const publicId = extractPublicId(text);

    if (!publicId) {
return;
}

    scanner?.pause();
    scanning.value = false;

    try {
        const result = await getJson<{ found: boolean; asset?: AssetInfo }>(`/escanear/activo/${publicId}`);

        if (result.found && result.asset) {
            foundAsset.value = result.asset;
            notFoundCode.value = null;
        } else {
            notFoundCode.value = publicId;
            foundAsset.value = null;
        }
    } catch {
        notFoundCode.value = publicId;
    }
}

async function startScanning() {
    cameraError.value = null;

    if (!videoRef.value) {
return;
}

    try {
        scanner = new QrScanner(videoRef.value, (result) => onDecoded(result.data), {
            highlightScanRegion: true,
            highlightCodeOutline: true,
            maxScansPerSecond: 5,
        });
        await scanner.start();
        scanning.value = true;
    } catch {
        cameraError.value = 'No se pudo acceder a la cámara. Revisa los permisos del navegador.';
    }
}

function resumeScanning() {
    foundAsset.value = null;
    notFoundCode.value = null;
    showIssueForm.value = false;
    issueComment.value = '';
    scanner?.start();
    scanning.value = true;
}

async function markFound(status: string, comment: string) {
    if (!foundAsset.value || !props.audit) {
return;
}

    marking.value = true;

    try {
        await postJson(`/auditorias/${props.audit.id}/marcar`, {
            asset_public_id: foundAsset.value.public_id,
            status,
            comment: comment || null,
        });
        markedCount.value += 1;
        resumeScanning();
    } finally {
        marking.value = false;
    }
}

onMounted(() => {
    startScanning();
});

onBeforeUnmount(() => {
    scanner?.stop();
    scanner?.destroy();
});
</script>

<template>
    <Head title="Escanear QR" />

    <div class="mx-auto flex max-w-md flex-col gap-4">
        <PageHeader title="Escanear QR" :description="audit ? `Auditoría: ${audit.name}` : 'Busca un activo apuntando la cámara a su etiqueta'" />

        <div v-if="audit" class="rounded-lg border border-border bg-card p-3 text-sm">
            <p class="font-medium">{{ markedCount }} activos registrados en esta sesión</p>
        </div>

        <div v-else-if="activeAudits.length > 0" class="grid gap-2">
            <p class="text-sm text-muted-foreground">O selecciona una auditoría activa para registrar hallazgos:</p>
            <Select @update:model-value="(value) => goToAudit(String(value))">
                <SelectTrigger class="w-full"><SelectValue placeholder="Sin auditoría (solo consulta)" /></SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="a in activeAudits" :key="a.id" :value="String(a.id)">{{ a.name }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-border bg-black">
            <video ref="video" class="aspect-square w-full object-cover"></video>
            <div v-if="!scanning && !cameraError" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white">
                <Camera class="size-8 animate-pulse" />
            </div>
        </div>

        <Alert v-if="cameraError" variant="destructive">
            <AlertTriangle class="size-4" />
            <AlertTitle>Cámara no disponible</AlertTitle>
            <AlertDescription>{{ cameraError }}</AlertDescription>
        </Alert>

        <div v-if="notFoundCode" class="rounded-xl border border-destructive/30 bg-destructive/5 p-4">
            <p class="text-sm font-medium text-destructive">No se encontró ningún activo con ese código.</p>
            <Button class="mt-3" variant="outline" @click="resumeScanning">Escanear otro</Button>
        </div>

        <div v-if="foundAsset" class="space-y-3 rounded-xl border border-border bg-card p-4">
            <div class="flex items-center gap-2">
                <CheckCircle2 class="size-5 text-emerald-600" />
                <div>
                    <p class="font-mono text-sm font-medium">{{ foundAsset.internal_code }}</p>
                    <p class="text-sm text-muted-foreground">{{ foundAsset.name }}</p>
                </div>
            </div>
            <div class="grid gap-1 text-xs text-muted-foreground">
                <p>Ubicación esperada: {{ foundAsset.branch }}<span v-if="foundAsset.department"> · {{ foundAsset.department }}</span></p>
                <p>Responsable esperado: {{ foundAsset.responsible ?? 'sin asignar' }}</p>
            </div>

            <template v-if="audit">
                <div v-if="!showIssueForm" class="flex gap-2">
                    <Button class="flex-1" :disabled="marking" @click="markFound('encontrado', '')">Todo correcto</Button>
                    <Button variant="outline" class="flex-1" @click="showIssueForm = true">Reportar diferencia</Button>
                </div>
                <div v-else class="space-y-3">
                    <Select v-model="issueStatus">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ubicacion_incorrecta">Ubicación incorrecta</SelectItem>
                            <SelectItem value="responsable_incorrecto">Responsable incorrecto</SelectItem>
                            <SelectItem value="requiere_revision">Requiere revisión</SelectItem>
                            <SelectItem value="danado">Dañado</SelectItem>
                            <SelectItem value="no_encontrado">No encontrado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Textarea v-model="issueComment" placeholder="Comentario (opcional)" rows="2" />
                    <div class="flex gap-2">
                        <Button variant="outline" class="flex-1" @click="showIssueForm = false">Cancelar</Button>
                        <Button class="flex-1" :disabled="marking" @click="markFound(issueStatus, issueComment)">Guardar</Button>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="flex gap-2">
                    <Link :href="`/activos/${foundAsset.public_id}`" class="flex-1">
                        <Button class="w-full">Ver ficha</Button>
                    </Link>
                    <Button variant="outline" class="flex-1" @click="resumeScanning">Escanear otro</Button>
                </div>
            </template>
        </div>

        <p v-if="scanning && !foundAsset && !notFoundCode" class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
            <QrCode class="size-4" />
            Apunta la cámara al código QR del activo
        </p>
    </div>
</template>
