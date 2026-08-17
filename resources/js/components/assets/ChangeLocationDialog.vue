<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Combobox from '@/components/Combobox.vue';
import InputError from '@/components/InputError.vue';
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
    branchId: number;
    departmentId: number | null;
    branches: { id: number; name: string }[];
    departments: { id: number; name: string }[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    branch_id: props.branchId as number | string,
    department_id: props.departmentId ?? ('' as number | string),
    comment: '',
});

function submit() {
    form.post(`/activos/${props.assetPublicId}/cambiar-ubicacion`, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Cambiar ubicación</DialogTitle>
                <DialogDescription>Se guardará en el historial del activo.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Sucursal</Label>
                    <Combobox v-model="form.branch_id" :options="branches.map((b) => ({ value: b.id, label: b.name }))" />
                    <InputError :message="form.errors.branch_id" />
                </div>
                <div class="grid gap-2">
                    <Label>Área (opcional)</Label>
                    <Combobox v-model="form.department_id" :options="departments.map((d) => ({ value: d.id, label: d.name }))" placeholder="Sin área" />
                    <InputError :message="form.errors.department_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="location-comment">Observación (opcional)</Label>
                    <Textarea id="location-comment" v-model="form.comment" rows="2" />
                    <InputError :message="form.errors.comment" />
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
