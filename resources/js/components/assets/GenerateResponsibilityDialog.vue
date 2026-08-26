<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
    /** true si el responsable actual ya tiene una responsiva generada. */
    alreadyHasOne: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const title = computed(() =>
    props.alreadyHasOne ? 'Generar nueva responsiva' : 'Generar responsiva',
);

const form = useForm({
    reason: '',
});

function submit() {
    form.post(`/activos/${props.assetPublicId}/responsivas`, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="alreadyHasOne">
                    Ya existe una responsiva para el responsable actual; esto
                    genera una adicional y queda registrado en el historial del
                    activo con tu usuario.
                </DialogDescription>
                <DialogDescription v-else>
                    Se preparará un borrador de responsiva para el responsable
                    actual, listo para revisar, imprimir o enviar a firma.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="responsibility-reason">
                        Motivo (opcional)
                    </Label>
                    <Textarea
                        id="responsibility-reason"
                        v-model="form.reason"
                        rows="2"
                        :placeholder="
                            alreadyHasOne
                                ? 'Ej. se corrigió el otorgante, se reemitió por error de datos...'
                                : 'Opcional'
                        "
                    />
                    <InputError :message="form.errors.reason" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        Generar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
