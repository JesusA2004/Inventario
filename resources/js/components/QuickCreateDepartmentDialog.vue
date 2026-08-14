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

type Department = { id: number; name: string; company_id: number | null };

const props = defineProps<{
    companyId: number | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [department: Department];
}>();

const name = ref('');
const processing = ref(false);
const errors = ref<Record<string, string[]>>({});

function reset() {
    name.value = '';
    errors.value = {};
}

async function submit() {
    processing.value = true;
    errors.value = {};

    try {
        const department = await postJson<Department>('/areas', {
            company_id: props.companyId,
            name: name.value,
            active: true,
        });
        emit('created', department);
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
                <DialogTitle>Nueva área / departamento</DialogTitle>
                <DialogDescription
                    >Se agregará al catálogo y quedará
                    seleccionada.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="quick-department-name">Nombre</Label>
                    <Input
                        id="quick-department-name"
                        v-model="name"
                        autofocus
                        placeholder="Ej. Sistemas"
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !name">
                        <Spinner v-if="processing" class="mr-1" />
                        Crear área
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
