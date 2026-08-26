<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    assetPublicId: string;
    currentResponsibleId: number | null;
    responsiblePeople: { id: number; full_name: string }[];
    documentSettings?: {
        default_grantor_name: string | null;
        default_grantor_position: string | null;
        auto_generate_on_assignment: boolean;
    };
    canOverrideGrantor?: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const dialogTitle = computed(() =>
    props.currentResponsibleId ? 'Cambiar responsable' : 'Asignar responsable',
);

const form = useForm({
    current_responsible_id:
        props.currentResponsibleId ?? ('' as number | string),
    comment: '',
    delivery_date: new Date().toISOString().slice(0, 10),
    generate_responsibility:
        props.documentSettings?.auto_generate_on_assignment ?? true,
    grantor_name: '',
    grantor_position: '',
    grantor_email: '',
});

// La responsiva solo aplica cuando en verdad se está asignando alguien (no
// al dejar el activo "sin asignar").
const hasRealResponsible = computed(
    () =>
        form.current_responsible_id !== '' &&
        form.current_responsible_id !== null,
);

function submit() {
    form.post(`/activos/${props.assetPublicId}/cambiar-responsable`, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription
                    >Se guardará en el historial del activo.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Nuevo responsable</Label>
                    <Combobox
                        v-model="form.current_responsible_id"
                        :options="
                            responsiblePeople.map((r) => ({
                                value: r.id,
                                label: r.full_name,
                            }))
                        "
                        placeholder="Sin asignar (almacenado)"
                    />
                    <InputError :message="form.errors.current_responsible_id" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <div class="grid gap-2">
                        <Label for="delivery-date">Fecha de entrega</Label>
                        <Input
                            id="delivery-date"
                            v-model="form.delivery_date"
                            type="date"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="responsible-comment"
                        >Observaciones (opcional)</Label
                    >
                    <Textarea
                        id="responsible-comment"
                        v-model="form.comment"
                        rows="2"
                    />
                    <InputError :message="form.errors.comment" />
                </div>

                <template v-if="hasRealResponsible">
                    <Separator />

                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <Label for="generate-responsibility"
                                >Generar responsiva de entrega</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Crea un borrador de responsiva de equipo listo
                                para imprimir o enviar a firma.
                            </p>
                        </div>
                        <Switch
                            id="generate-responsibility"
                            v-model="form.generate_responsibility"
                        />
                    </div>

                    <div
                        v-if="
                            form.generate_responsibility && canOverrideGrantor
                        "
                        class="grid gap-3 rounded-lg border border-border bg-muted/40 p-3"
                    >
                        <p class="text-xs font-medium text-muted-foreground">
                            Otorgante (por defecto:
                            {{
                                documentSettings?.default_grantor_name ??
                                'sin configurar'
                            }})
                        </p>
                        <div class="grid gap-2">
                            <Label for="grantor-name"
                                >Nombre del otorgante</Label
                            >
                            <Input
                                id="grantor-name"
                                v-model="form.grantor_name"
                                :placeholder="
                                    documentSettings?.default_grantor_name ?? ''
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="grantor-position"
                                >Puesto del otorgante</Label
                            >
                            <Input
                                id="grantor-position"
                                v-model="form.grantor_position"
                                :placeholder="
                                    documentSettings?.default_grantor_position ??
                                    ''
                                "
                            />
                        </div>
                        <InputError :message="form.errors.grantor_name" />
                    </div>
                    <p
                        v-else-if="form.generate_responsibility"
                        class="text-xs text-muted-foreground"
                    >
                        Otorgante:
                        {{
                            documentSettings?.default_grantor_name ??
                            'sin configurar'
                        }}
                    </p>
                </template>

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
