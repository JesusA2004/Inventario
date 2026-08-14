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

type Branch = { id: number; name: string; company_id: number };

const props = defineProps<{
    companyId: number | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [branch: Branch];
}>();

const name = ref('');
const code = ref('');
const processing = ref(false);
const errors = ref<Record<string, string[]>>({});

function reset() {
    name.value = '';
    code.value = '';
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
        const branch = await postJson<Branch>('/sucursales', {
            company_id: props.companyId,
            name: name.value,
            code: code.value,
            active: true,
        });
        emit('created', branch);
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
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Nueva sucursal</DialogTitle>
                <DialogDescription
                    >Se agregará a la empresa seleccionada y quedará
                    elegida.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="quick-branch-name">Nombre</Label>
                    <Input
                        id="quick-branch-name"
                        v-model="name"
                        autofocus
                        placeholder="Ej. Corporativo"
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>
                <div class="grid gap-2">
                    <Label for="quick-branch-code">Código</Label>
                    <Input
                        id="quick-branch-code"
                        v-model="code"
                        placeholder="Ej. CORP"
                        maxlength="20"
                    />
                    <InputError :message="errors.code?.[0]" />
                </div>
                <InputError :message="errors.company_id?.[0]" />

                <DialogFooter>
                    <Button
                        type="submit"
                        :disabled="processing || !name || !code"
                    >
                        <Spinner v-if="processing" class="mr-1" />
                        Crear sucursal
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
