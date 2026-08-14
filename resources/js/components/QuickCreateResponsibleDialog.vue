<script setup lang="ts">
import { ref } from 'vue';
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
import { Spinner } from '@/components/ui/spinner';
import type { ApiValidationError } from '@/lib/http';
import { postJson } from '@/lib/http';

type Responsible = { id: number; full_name: string; company_id: number };

const props = defineProps<{
    companyId: number | null;
    branchId?: number | null;
    departmentId?: number | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [responsible: Responsible];
}>();

const fullName = ref('');
const position = ref('');
const email = ref('');
const phone = ref('');
const processing = ref(false);
const errors = ref<Record<string, string[]>>({});

function reset() {
    fullName.value = '';
    position.value = '';
    email.value = '';
    phone.value = '';
    errors.value = {};
}

async function submit() {
    if (!props.companyId) {
        errors.value = { company_id: ['Selecciona primero la empresa.'] };

        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        const responsible = await postJson<Responsible>('/responsables', {
            company_id: props.companyId,
            branch_id: props.branchId ?? null,
            department_id: props.departmentId ?? null,
            full_name: fullName.value,
            position: position.value || null,
            email: email.value || null,
            phone: phone.value || null,
            active: true,
        });
        emit('created', responsible);
        open.value = false;
        reset();
    } catch (error) {
        errors.value = (error as ApiValidationError).errors ?? {};
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Nuevo responsable</DialogTitle>
                <DialogDescription
                    >Se agregará al catálogo y quedará
                    seleccionado.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="quick-resp-name">Nombre completo</Label>
                    <Input
                        id="quick-resp-name"
                        v-model="fullName"
                        autofocus
                        placeholder="Ej. Jesús Arizmendi"
                    />
                    <InputError :message="errors.full_name?.[0]" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="quick-resp-position">Puesto</Label>
                        <Input
                            id="quick-resp-position"
                            v-model="position"
                            placeholder="Opcional"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="quick-resp-phone">Teléfono</Label>
                        <Input
                            id="quick-resp-phone"
                            v-model="phone"
                            placeholder="Opcional"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="quick-resp-email">Correo</Label>
                    <Input
                        id="quick-resp-email"
                        v-model="email"
                        type="email"
                        placeholder="Opcional"
                    />
                    <InputError :message="errors.email?.[0]" />
                </div>
                <InputError :message="errors.company_id?.[0]" />

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !fullName">
                        <Spinner v-if="processing" class="mr-1" />
                        Crear responsable
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
