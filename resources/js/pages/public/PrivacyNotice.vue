<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    version?: number;
    body?: string;
}>();
</script>

<template>
    <Head title="Aviso de privacidad" />

    <div class="min-h-dvh bg-slate-50 px-4 py-10">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="flex items-center gap-2">
                <span
                    class="flex size-9 items-center justify-center rounded-lg bg-[hsl(217_40%_13%)]"
                >
                    <AppLogoIcon class="size-4 fill-current text-white" />
                </span>
                <span class="text-sm font-medium text-slate-600"
                    >MR LANA · Inventario</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
            >
                <h1 class="text-xl font-semibold text-slate-900">
                    Aviso de privacidad
                </h1>
                <p v-if="version" class="mt-1 text-xs text-slate-400">
                    Versión {{ version }}
                </p>

                <div
                    v-if="body"
                    class="prose prose-slate mt-6 max-w-none text-sm whitespace-pre-line text-slate-700"
                >
                    {{ body }}
                </div>
                <p v-else class="mt-6 text-sm text-slate-500">
                    No hay un aviso de privacidad configurado todavía.
                </p>
            </div>
        </div>
    </div>
</template>
