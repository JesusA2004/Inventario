<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
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

const props = defineProps<{
    assetPublicId: string;
    reasons: { value: string; label: string }[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    reason: '',
    notes: '',
});

function submit() {
    form.post(`/activos/${props.assetPublicId}/baja`, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <AlertDialog :open="open" @update:open="(value) => (open = value)">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Dar de baja este activo</AlertDialogTitle>
                <AlertDialogDescription>
                    El activo dejará de contarse como parte del inventario activo, pero todo su historial se conservará.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Fecha de baja</Label>
                    <DatePicker v-model="form.date" from-today />
                    <InputError :message="form.errors.date" />
                </div>
                <div class="grid gap-2">
                    <Label>Motivo</Label>
                    <Select v-model="form.reason">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un motivo" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="reason in reasons" :key="reason.value" :value="reason.value">{{ reason.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.reason" />
                </div>
                <div class="grid gap-2">
                    <Label for="decommission-notes">Observaciones (opcional)</Label>
                    <Textarea id="decommission-notes" v-model="form.notes" rows="2" />
                    <InputError :message="form.errors.notes" />
                </div>
                <AlertDialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">Cancelar</Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="form.processing || !form.reason"
                    >
                        <Spinner v-if="form.processing" class="mr-1" />
                        Dar de baja
                    </Button>
                </AlertDialogFooter>
            </form>
        </AlertDialogContent>
    </AlertDialog>
</template>
