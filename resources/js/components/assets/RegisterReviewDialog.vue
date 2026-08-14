<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import DatePicker from '@/components/DatePicker.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    assetPublicId: string;
}>();

const open = defineModel<boolean>('open', { default: false });

const physicalStatusOptions = ['Bueno', 'Regular', 'Dañado', 'Requiere mantenimiento'];

const form = useForm({
    reviewed_at: new Date().toISOString().slice(0, 10),
    physical_status: 'Bueno',
    location_ok: true,
    responsible_ok: true,
    notes: '',
});

function submit() {
    form.post(`/activos/${props.assetPublicId}/revision`, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Registrar revisión</DialogTitle>
                <DialogDescription>Actualiza la fecha de última revisión del activo.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Fecha de revisión</Label>
                    <DatePicker v-model="form.reviewed_at" from-today />
                </div>
                <div class="grid gap-2">
                    <Label>Estado físico</Label>
                    <Select v-model="form.physical_status">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in physicalStatusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="text-sm font-medium">Ubicación correcta</p>
                    <Switch v-model="form.location_ok" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border p-3">
                    <p class="text-sm font-medium">Responsable correcto</p>
                    <Switch v-model="form.responsible_ok" />
                </div>
                <div class="grid gap-2">
                    <Label for="review-notes">Observaciones (opcional)</Label>
                    <Textarea id="review-notes" v-model="form.notes" rows="2" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        Registrar revisión
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
