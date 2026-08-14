<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        confirmText?: string;
        cancelText?: string;
        destructive?: boolean;
        loading?: boolean;
    }>(),
    {
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        destructive: false,
        loading: false,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

function onConfirm() {
    emit('confirm');
}
</script>

<template>
    <AlertDialog
        :open="open"
        @update:open="(value) => emit('update:open', value)"
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="description">{{
                    description
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="loading">{{
                    cancelText
                }}</AlertDialogCancel>
                <AlertDialogAction
                    :class="
                        destructive
                            ? 'bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20'
                            : ''
                    "
                    :disabled="loading"
                    @click.prevent="onConfirm"
                >
                    <Spinner v-if="loading" class="mr-1" />
                    {{ confirmText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
