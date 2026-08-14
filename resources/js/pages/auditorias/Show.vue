<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { QrCode } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type AuditItem = {
    id: number;
    status: { value: string; label: string; color: string };
    comment: string | null;
    checked_by: string | null;
    asset: {
        id: number;
        public_id: string;
        internal_code: string;
        name: string;
        expected_branch: string | null;
        expected_department: string | null;
        expected_responsible: string | null;
    };
    found_branch: string | null;
    found_department: string | null;
    found_responsible: string | null;
};

const props = defineProps<{
    audit: {
        id: number;
        name: string;
        status: { value: string; label: string; color: string };
        started_at: string;
        finished_at: string | null;
        company: string | null;
        branch: string | null;
        department: string | null;
    };
    items: AuditItem[];
    stats: { total: number; found: number; pending: number; missing: number; differences: number; percent: number };
    itemStatusOptions: { value: string; label: string; color: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Auditorías', href: '/auditorias' },
        { title: props.audit.name, href: `/auditorias/${props.audit.id}` },
    ],
});

const filter = ref<'all' | 'pendiente' | 'encontrado' | 'no_encontrado' | 'diferencias'>('all');

const filteredItems = computed(() => {
    if (filter.value === 'all') {
return props.items;
}

    if (filter.value === 'diferencias') {
        return props.items.filter((i) => !['pendiente', 'encontrado'].includes(i.status.value));
    }

    return props.items.filter((i) => i.status.value === filter.value);
});

const markDialogItem = ref<AuditItem | null>(null);
const markForm = useForm({ asset_public_id: '', status: 'encontrado', comment: '' });

function openMark(item: AuditItem) {
    markDialogItem.value = item;
    markForm.asset_public_id = item.asset.public_id;
    markForm.status = item.status.value === 'pendiente' ? 'encontrado' : item.status.value;
    markForm.comment = item.comment ?? '';
}

function submitMark() {
    markForm.post(`/auditorias/${props.audit.id}/marcar`, {
        preserveScroll: true,
        onSuccess: () => (markDialogItem.value = null),
    });
}

const finishConfirmOpen = ref(false);
const finishing = ref(false);

function finishAudit() {
    finishing.value = true;
    router.post(
        `/auditorias/${props.audit.id}/finalizar`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                finishing.value = false;
                finishConfirmOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="audit.name" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">{{ audit.name }}</h1>
                    <StatusBadge :label="audit.status.label" :color="audit.status.color" />
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ audit.company }} · {{ audit.branch }}<span v-if="audit.department"> · {{ audit.department }}</span>
                </p>
            </div>
            <div v-if="audit.status.value === 'en_progreso'" class="flex items-center gap-2">
                <Link :href="`/escanear?audit_id=${audit.id}`">
                    <Button>
                        <QrCode class="mr-1 size-4" />
                        Escanear
                    </Button>
                </Link>
                <Button variant="outline" @click="finishConfirmOpen = true">Finalizar</Button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <StatCard title="Esperados" :value="stats.total" />
            <StatCard title="Encontrados" :value="stats.found" tone="positive" />
            <StatCard title="Faltantes" :value="stats.missing" tone="destructive" />
            <StatCard title="Diferencias" :value="stats.differences" tone="warning" />
            <StatCard title="% revisado" :value="`${stats.percent}%`" />
        </div>

        <div class="flex flex-wrap gap-2">
            <Button v-for="option in [{ value: 'all', label: 'Todos' }, { value: 'pendiente', label: 'Pendientes' }, { value: 'encontrado', label: 'Encontrados' }, { value: 'no_encontrado', label: 'No encontrados' }, { value: 'diferencias', label: 'Con diferencias' }]" :key="option.value" :variant="filter === option.value ? 'default' : 'outline'" size="sm" @click="filter = option.value as typeof filter">
                {{ option.label }}
            </Button>
        </div>

        <div class="space-y-2">
            <div v-for="item in filteredItems" :key="item.id" class="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-3">
                <div class="min-w-0">
                    <p class="truncate font-medium text-foreground">{{ item.asset.name }}</p>
                    <p class="font-mono text-xs text-muted-foreground">{{ item.asset.internal_code }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge :label="item.status.label" :color="item.status.color" />
                    <Button variant="ghost" size="sm" @click="openMark(item)">Marcar</Button>
                </div>
            </div>
            <p v-if="filteredItems.length === 0" class="py-8 text-center text-sm text-muted-foreground">No hay activos en este filtro.</p>
        </div>
    </div>

    <Dialog :open="!!markDialogItem" @update:open="(value) => !value && (markDialogItem = null)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>{{ markDialogItem?.asset.name }}</DialogTitle>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitMark">
                <div v-if="markDialogItem" class="rounded-lg border border-border bg-muted/50 p-3 text-xs text-muted-foreground">
                    <p>Esperado: {{ markDialogItem.asset.expected_branch }} · {{ markDialogItem.asset.expected_department ?? 'sin área' }}</p>
                    <p>Responsable esperado: {{ markDialogItem.asset.expected_responsible ?? 'sin asignar' }}</p>
                </div>
                <div class="grid gap-2">
                    <Label>Resultado</Label>
                    <Select v-model="markForm.status">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in itemStatusOptions.filter((o) => o.value !== 'pendiente')" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="mark-comment">Comentario (opcional)</Label>
                    <Textarea id="mark-comment" v-model="markForm.comment" rows="2" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="markForm.processing">
                        <Spinner v-if="markForm.processing" class="mr-1" />
                        Guardar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="finishConfirmOpen"
        title="¿Finalizar esta auditoría?"
        description="Los activos pendientes se marcarán automáticamente como no encontrados."
        confirm-text="Finalizar"
        :loading="finishing"
        @update:open="(value) => (finishConfirmOpen = value)"
        @confirm="finishAudit"
    />
</template>
