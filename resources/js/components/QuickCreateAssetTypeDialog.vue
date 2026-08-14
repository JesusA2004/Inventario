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

type AssetType = { id: number; name: string; code: string };

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [assetType: AssetType];
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
    processing.value = true;
    errors.value = {};

    try {
        const assetType = await postJson<AssetType>('/tipos-activo', {
            name: name.value,
            code: code.value,
            active: true,
        });
        emit('created', assetType);
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
                <DialogTitle>Nuevo tipo de activo</DialogTitle>
                <DialogDescription
                    >Se agregará al catálogo y quedará
                    seleccionado.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="quick-type-name">Nombre</Label>
                    <Input
                        id="quick-type-name"
                        v-model="name"
                        autofocus
                        placeholder="Ej. Escáner de código de barras"
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>
                <div class="grid gap-2">
                    <Label for="quick-type-code">Código corto</Label>
                    <Input
                        id="quick-type-code"
                        v-model="code"
                        placeholder="Ej. SCN"
                        maxlength="10"
                        class="uppercase"
                    />
                    <InputError :message="errors.code?.[0]" />
                </div>

                <DialogFooter>
                    <Button
                        type="submit"
                        :disabled="processing || !name || !code"
                    >
                        <Spinner v-if="processing" class="mr-1" />
                        Crear tipo
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
