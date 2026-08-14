<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, Puzzle, ShieldCheck, Tag } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import StatusBadge from '@/components/StatusBadge.vue';

type PublicPart = {
    internal_code: string;
    name: string;
    part_number: string | null;
    serial_number: string | null;
    status: { label: string; color: string };
    in_inventory: boolean;
    assembled: boolean;
    company_name: string | null;
    branch_name: string | null;
    brand_name: string | null;
    related_asset_code: string | null;
};

defineProps<{ part: PublicPart }>();

const appName = usePage().props.name;
</script>

<template>
    <Head :title="`${part.internal_code} · ${part.name}`" />

    <div class="min-h-dvh bg-[hsl(217_40%_13%)] px-4 py-8">
        <div class="mx-auto flex max-w-md flex-col gap-4">
            <div class="flex flex-col items-center gap-2 pb-2 text-center">
                <span class="flex size-10 items-center justify-center rounded-lg bg-[hsl(206_80%_55%)]">
                    <AppLogoIcon class="size-5 fill-current text-[hsl(217_40%_12%)]" />
                </span>
                <p class="text-sm font-medium text-white/70">{{ appName }} · Inventario de TI</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white shadow-xl">
                <div class="bg-[hsl(217_40%_13%)] px-6 py-5 text-center">
                    <p class="text-xs font-semibold tracking-wide text-white/60 uppercase">Pieza / Refacción</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-white">{{ part.internal_code }}</p>
                    <p class="mt-1 text-sm text-white/80">{{ part.name }}</p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <StatusBadge :label="part.status.label" :color="part.status.color" />
                        <span v-if="part.assembled" class="text-xs font-medium text-emerald-600">Ensamblada</span>
                    </div>

                    <div class="grid gap-3 text-sm">
                        <div class="flex items-start gap-3">
                            <Building2 class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="font-medium text-slate-900">{{ part.company_name ?? '—' }}</p>
                                <p class="text-slate-500">{{ part.branch_name }}</p>
                            </div>
                        </div>
                        <div v-if="part.brand_name || part.part_number || part.serial_number" class="flex items-start gap-3">
                            <Tag class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <div class="text-slate-700">
                                <p v-if="part.brand_name">{{ part.brand_name }}</p>
                                <p v-if="part.part_number" class="text-xs text-slate-500">P/N: {{ part.part_number }}</p>
                                <p v-if="part.serial_number" class="text-xs text-slate-500">S/N: {{ part.serial_number }}</p>
                            </div>
                        </div>
                        <div v-if="part.related_asset_code" class="flex items-start gap-3">
                            <Puzzle class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <p class="text-slate-700">Vinculada al activo {{ part.related_asset_code }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3">
                    <ShieldCheck class="size-3.5 text-slate-400" />
                    <p class="text-xs text-slate-400">Código QR permanente de inventario</p>
                </div>
            </div>
        </div>
    </div>
</template>
