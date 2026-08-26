<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Ban,
    ChevronDown,
    ChevronUp,
    Download,
    Eye,
    FileUp,
    GripVertical,
    Mail,
    Plus,
    Send,
    ShieldCheck,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Characteristic = { name: string; value: string };

type ResponsibilityDetail = {
    id: number;
    public_id: string;
    document_number: string | null;
    status: { value: string; label: string; color: string };
    receiver_name: string;
    receiver_position: string | null;
    receiver_email: string | null;
    grantor_name: string;
    grantor_position: string | null;
    issued_at: string | null;
    city: string | null;
    document_title: string | null;
    asset_snapshot: {
        asset: {
            internal_code?: string;
            name?: string;
            type?: string;
            brand?: string;
            model?: string;
            serial_number?: string;
            status?: string;
        } | null;
        company: { name?: string; legal_name?: string } | null;
        characteristics: Characteristic[];
        legal_review_status: string | null;
    };
    has_unsigned_pdf: boolean;
    has_signed_pdf: boolean;
    has_physical_copy: boolean;
    sent_at: string | null;
    viewed_at: string | null;
    signed_at: string | null;
    physical_received_at: string | null;
    created_at: string | null;
    asset: { public_id: string; internal_code: string; name: string } | null;
    company: { id: number; name: string } | null;
    responsiblePerson: {
        id: number;
        full_name: string;
        email: string | null;
    } | null;
    creator: { id: number; name: string } | null;
    physicalReceivedBy: { name: string } | null;
    evidence?: {
        signer_ip: string | null;
        signer_user_agent: string | null;
        signature_sha256: string | null;
        document_sha256: string | null;
        legal_template_version: number;
        privacy_notice_version: number;
        verification_code: string | null;
    };
};

const props = defineProps<{ responsibility: ResponsibilityDetail }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Responsivas', href: '/responsivas' }] },
});

const previewOpen = ref(false);

const isDraft = computed(
    () => props.responsibility.status.value === 'borrador',
);
const isFinal = computed(() =>
    ['firmada_digitalmente', 'firma_fisica_cargada', 'cancelada'].includes(
        props.responsibility.status.value,
    ),
);
const canSend = computed(() =>
    ['borrador'].includes(props.responsibility.status.value),
);
const canResend = computed(() =>
    ['enviada', 'vista'].includes(props.responsibility.status.value),
);

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

// --- Características (editable solo en borrador) ---
const characteristics = ref<Characteristic[]>(
    props.responsibility.asset_snapshot.characteristics.map((c) => ({ ...c })),
);
const savingCharacteristics = ref(false);

function addCharacteristic() {
    characteristics.value.push({ name: '', value: '' });
}

function removeCharacteristic(index: number) {
    characteristics.value.splice(index, 1);
}

function moveCharacteristic(index: number, direction: -1 | 1) {
    const target = index + direction;

    if (target < 0 || target >= characteristics.value.length) {
        return;
    }

    const [item] = characteristics.value.splice(index, 1);
    characteristics.value.splice(target, 0, item);
}

function saveCharacteristics() {
    savingCharacteristics.value = true;
    router.patch(
        `/responsivas/${props.responsibility.public_id}/caracteristicas`,
        { characteristics: characteristics.value },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Características actualizadas.'),
            onFinish: () => (savingCharacteristics.value = false),
        },
    );
}

// --- Acciones de estado ---
const sending = ref(false);

function send() {
    sending.value = true;
    router.post(
        `/responsivas/${props.responsibility.public_id}/enviar`,
        {},
        { preserveScroll: true, onFinish: () => (sending.value = false) },
    );
}

function resend() {
    sending.value = true;
    router.post(
        `/responsivas/${props.responsibility.public_id}/reenviar`,
        {},
        { preserveScroll: true, onFinish: () => (sending.value = false) },
    );
}

const cancelling = ref(false);
const cancelConfirmOpen = ref(false);

function cancel() {
    cancelling.value = true;
    router.post(
        `/responsivas/${props.responsibility.public_id}/cancelar`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancelling.value = false;
                cancelConfirmOpen.value = false;
            },
        },
    );
}

// --- Copia física ---
const physicalDialogOpen = ref(false);
const physicalForm = useForm<{ file: File | null; received_at: string }>({
    file: null,
    received_at: new Date().toISOString().slice(0, 10),
});

function submitPhysical() {
    physicalForm.post(
        `/responsivas/${props.responsibility.public_id}/copia-fisica`,
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                physicalDialogOpen.value = false;
                physicalForm.reset();
            },
        },
    );
}
</script>

<template>
    <Head :title="responsibility.document_number ?? 'Responsiva'" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1
                        class="text-2xl font-semibold tracking-tight text-foreground"
                    >
                        {{
                            responsibility.document_title ??
                            'Responsiva de equipo'
                        }}
                    </h1>
                    <StatusBadge
                        :label="responsibility.status.label"
                        :color="responsibility.status.color"
                    />
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ responsibility.document_number }}
                    <span v-if="responsibility.asset">
                        · {{ responsibility.asset.internal_code }} —
                        {{ responsibility.asset.name }}</span
                    >
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" @click="previewOpen = true">
                    <Eye class="mr-1 size-4" /> Vista previa
                </Button>
                <a
                    :href="`/responsivas/${responsibility.public_id}/pdf`"
                    target="_blank"
                >
                    <Button variant="outline"
                        ><Download class="mr-1 size-4" /> PDF</Button
                    >
                </a>
                <a
                    v-if="responsibility.has_signed_pdf"
                    :href="`/responsivas/${responsibility.public_id}/pdf-firmado`"
                    target="_blank"
                >
                    <Button variant="outline"
                        ><ShieldCheck class="mr-1 size-4" /> PDF firmado</Button
                    >
                </a>
                <Button
                    v-if="canSend"
                    :disabled="sending || !responsibility.receiver_email"
                    @click="send"
                >
                    <Spinner v-if="sending" class="mr-1" />
                    <Send v-else class="mr-1 size-4" />
                    Enviar para firma
                </Button>
                <Button
                    v-if="canResend"
                    variant="outline"
                    :disabled="sending"
                    @click="resend"
                >
                    <Spinner v-if="sending" class="mr-1" />
                    <Mail v-else class="mr-1 size-4" />
                    Reenviar invitación
                </Button>
                <Button
                    v-if="!isFinal"
                    variant="outline"
                    @click="physicalDialogOpen = true"
                >
                    <FileUp class="mr-1 size-4" /> Subir copia física
                </Button>
                <Button
                    v-if="!isFinal"
                    variant="ghost"
                    class="text-destructive hover:text-destructive"
                    @click="cancelConfirmOpen = true"
                >
                    <Ban class="mr-1 size-4" /> Cancelar
                </Button>
            </div>
        </div>

        <div
            v-if="canSend && !responsibility.receiver_email"
            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400"
        >
            Este responsable no tiene correo electrónico. Puedes seguir
            imprimiendo el PDF; para enviarlo a firma electrónica, agrega su
            correo desde
            <Link href="/responsables" class="font-medium underline"
                >Responsables</Link
            >.
        </div>

        <div
            class="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div>
                <p class="text-xs text-muted-foreground">Receptor</p>
                <p class="text-sm font-medium">
                    {{ responsibility.receiver_name }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ responsibility.receiver_position ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Otorgante</p>
                <p class="text-sm font-medium">
                    {{ responsibility.grantor_name }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ responsibility.grantor_position ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Empresa</p>
                <p class="text-sm font-medium">
                    {{
                        responsibility.asset_snapshot.company?.legal_name ??
                        responsibility.company?.name ??
                        '—'
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ responsibility.city ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Fechas</p>
                <p class="text-xs">
                    Enviada: {{ formatDateTime(responsibility.sent_at) }}
                </p>
                <p class="text-xs">
                    Vista: {{ formatDateTime(responsibility.viewed_at) }}
                </p>
                <p class="text-xs">
                    Firmada: {{ formatDateTime(responsibility.signed_at) }}
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-foreground">
                    Características del equipo
                </h2>
                <Button
                    v-if="isDraft"
                    size="sm"
                    variant="outline"
                    @click="addCharacteristic"
                >
                    <Plus class="mr-1 size-3.5" /> Agregar característica
                </Button>
            </div>

            <div class="space-y-2">
                <div
                    v-for="(row, index) in characteristics"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <GripVertical
                        class="size-4 shrink-0 text-muted-foreground/50"
                    />
                    <Input
                        v-model="row.name"
                        :disabled="!isDraft"
                        placeholder="Nombre"
                        class="w-1/3"
                    />
                    <Input
                        v-model="row.value"
                        :disabled="!isDraft"
                        placeholder="Descripción detallada"
                        class="flex-1"
                    />
                    <template v-if="isDraft">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 shrink-0"
                            :disabled="index === 0"
                            @click="moveCharacteristic(index, -1)"
                        >
                            <ChevronUp class="size-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 shrink-0"
                            :disabled="index === characteristics.length - 1"
                            @click="moveCharacteristic(index, 1)"
                        >
                            <ChevronDown class="size-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 shrink-0 text-destructive hover:text-destructive"
                            @click="removeCharacteristic(index)"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </template>
                </div>
            </div>

            <Button
                v-if="isDraft"
                class="mt-4"
                :disabled="savingCharacteristics"
                @click="saveCharacteristics"
            >
                <Spinner v-if="savingCharacteristics" class="mr-1" />
                Guardar características
            </Button>
            <p v-else class="mt-3 text-xs text-muted-foreground">
                Esta responsiva ya no está en borrador: sus características
                quedaron congeladas al emitirse.
            </p>
        </div>

        <div
            v-if="responsibility.has_physical_copy"
            class="rounded-xl border border-border bg-card p-4"
        >
            <h2 class="mb-1 text-sm font-semibold text-foreground">
                Copia física recibida
            </h2>
            <p class="text-sm text-muted-foreground">
                {{ formatDateTime(responsibility.physical_received_at) }}
                <span v-if="responsibility.physicalReceivedBy">
                    · Subida por
                    {{ responsibility.physicalReceivedBy.name }}</span
                >
            </p>
            <a
                :href="`/responsivas/${responsibility.public_id}/copia-fisica`"
                target="_blank"
                class="mt-2 inline-block"
            >
                <Button variant="outline" size="sm"
                    ><Download class="mr-1 size-3.5" /> Descargar</Button
                >
            </a>
        </div>

        <div
            v-if="responsibility.evidence"
            class="rounded-xl border border-border bg-card p-4"
        >
            <h2 class="mb-2 text-sm font-semibold text-foreground">
                Evidencia de firma
            </h2>
            <div
                class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2"
            >
                <p>
                    Versión de plantilla legal:
                    {{ responsibility.evidence.legal_template_version }}
                </p>
                <p>
                    Versión de aviso de privacidad:
                    {{ responsibility.evidence.privacy_notice_version }}
                </p>
                <p v-if="responsibility.evidence.verification_code">
                    Código de verificación:
                    {{ responsibility.evidence.verification_code }}
                </p>
                <p
                    v-if="responsibility.evidence.document_sha256"
                    class="truncate"
                >
                    Hash del documento:
                    {{ responsibility.evidence.document_sha256 }}
                </p>
                <p v-if="responsibility.evidence.signer_ip">
                    IP del firmante: {{ responsibility.evidence.signer_ip }}
                </p>
                <p
                    v-if="responsibility.evidence.signer_user_agent"
                    class="truncate"
                >
                    Navegador: {{ responsibility.evidence.signer_user_agent }}
                </p>
            </div>
        </div>
    </div>

    <Dialog v-model:open="physicalDialogOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Subir copia física firmada</DialogTitle>
                <DialogDescription
                    >PDF, JPG o PNG, máximo 10 MB.</DialogDescription
                >
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitPhysical">
                <div class="grid gap-2">
                    <Label for="physical-file">Archivo</Label>
                    <Input
                        id="physical-file"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="
                            physicalForm.file =
                                ($event.target as HTMLInputElement)
                                    .files?.[0] ?? null
                        "
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="physical-date">Fecha de recepción</Label>
                    <Input
                        id="physical-date"
                        v-model="physicalForm.received_at"
                        type="date"
                    />
                </div>
                <DialogFooter>
                    <Button
                        type="submit"
                        :disabled="
                            physicalForm.processing || !physicalForm.file
                        "
                    >
                        <Spinner v-if="physicalForm.processing" class="mr-1" />
                        Guardar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="cancelConfirmOpen"
        title="¿Cancelar esta responsiva?"
        description="Ya no podrá enviarse ni firmarse. Esta acción no se puede deshacer."
        confirm-text="Cancelar responsiva"
        destructive
        :loading="cancelling"
        @update:open="(value) => (cancelConfirmOpen = value)"
        @confirm="cancel"
    />

    <Dialog v-model:open="previewOpen">
        <DialogContent class="flex h-[90vh] max-w-4xl flex-col p-0">
            <DialogHeader class="border-b border-border px-4 py-3">
                <DialogTitle>Vista previa</DialogTitle>
                <DialogDescription>
                    Mismo formato y texto del PDF final; la paginación real solo
                    se calcula al generarlo.
                </DialogDescription>
            </DialogHeader>
            <iframe
                v-if="previewOpen"
                :src="`/responsivas/${responsibility.public_id}/vista-previa`"
                class="min-h-0 flex-1 rounded-b-lg border-0 bg-white"
                title="Vista previa de la responsiva"
            />
        </DialogContent>
    </Dialog>
</template>
