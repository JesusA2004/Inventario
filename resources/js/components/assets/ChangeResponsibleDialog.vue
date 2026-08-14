<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Combobox from '@/components/Combobox.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    assetPublicId: string;
    currentResponsibleId: number | null;
    responsiblePeople: { id: number; full_name: string }[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    current_responsible_id: props.currentResponsibleId ?? ('' as number | string),
    comment: '',
});

function submit() {
    form.post(`/activos/${props.assetPublicId}/cambiar-responsable`, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Cambiar responsable</DialogTitle>
                <DialogDescription>Se guardará en el historial del activo.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Nuevo responsable</Label>
                    <Combobox
                        v-model="form.current_responsible_id"
                        :options="responsiblePeople.map((r) => ({ value: r.id, label: r.full_name }))"
                        placeholder="Sin asignar (almacenado)"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="responsible-comment">Observación (opcional)</Label>
                    <Textarea id="responsible-comment" v-model="form.comment" rows="2" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        Guardar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
