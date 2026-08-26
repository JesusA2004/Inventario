<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CheckCircle2, Download, Eye, Mail, Printer } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    responsibility: {
        public_id: string;
        receiver_name: string;
        has_email: boolean;
    } | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const sending = ref(false);

function send() {
    if (!props.responsibility) {
        return;
    }

    sending.value = true;
    router.post(
        `/responsivas/${props.responsibility.public_id}/enviar`,
        {},
        { preserveScroll: true, onFinish: () => (sending.value = false) },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="mb-1 flex items-center gap-2 text-emerald-600">
                    <CheckCircle2 class="size-5" />
                    <DialogTitle>Equipo asignado correctamente</DialogTitle>
                </div>
                <DialogDescription>
                    Responsiva preparada para
                    <span class="font-medium text-foreground">{{
                        responsibility?.receiver_name
                    }}</span>
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="responsibility && !responsibility.has_email"
                class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400"
            >
                Este responsable no tiene correo electrónico registrado. Puedes
                imprimir la responsiva en papel, o
                <a href="/responsables" class="font-medium underline"
                    >agregar su correo</a
                >
                para poder enviarla a firma electrónica.
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <Button variant="outline" as-child>
                    <a :href="`/responsivas/${responsibility?.public_id}`">
                        <Eye class="mr-1 size-4" />
                        Ver responsiva
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="`/responsivas/${responsibility?.public_id}/pdf`"
                        target="_blank"
                    >
                        <Download class="mr-1 size-4" />
                        Descargar PDF
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="`/responsivas/${responsibility?.public_id}/pdf`"
                        target="_blank"
                    >
                        <Printer class="mr-1 size-4" />
                        Imprimir
                    </a>
                </Button>
                <Button
                    :disabled="!responsibility?.has_email || sending"
                    @click="send"
                >
                    <Spinner v-if="sending" class="mr-1" />
                    <Mail v-else class="mr-1 size-4" />
                    Enviar para firma
                </Button>
            </div>

            <DialogFooter>
                <Button variant="ghost" @click="open = false">Más tarde</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
