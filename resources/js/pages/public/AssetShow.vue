<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, CalendarCheck, MapPin, ShieldCheck, Tag, User } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import StatusBadge from '@/components/StatusBadge.vue';

type PublicAsset = {
    internal_code: string;
    name: string;
    model: string | null;
    serial_number: string | null;
    status: { label: string; color: string } | null;
    in_inventory: boolean;
    company_name: string | null;
    branch_name: string | null;
    department_name: string | null;
    asset_type_name: string | null;
    brand_name: string | null;
    responsible_name: string | null;
    last_reviewed_at: string | null;
};

defineProps<{ asset: PublicAsset }>();

const appName = usePage().props.name;

function formatDate(value: string | null): string {
    if (!value) {
return 'Sin registrar';
}

    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <Head :title="`${asset.internal_code} · ${asset.name}`" />

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
                    <p class="text-xs font-semibold tracking-wide text-white/60 uppercase">{{ asset.asset_type_name ?? 'Activo' }}</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-white">{{ asset.internal_code }}</p>
                    <p class="mt-1 text-sm text-white/80">{{ asset.name }}</p>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <StatusBadge v-if="asset.status" :label="asset.status.label" :color="asset.status.color" />
                        <span v-if="!asset.in_inventory" class="text-xs font-medium text-red-600">Fuera de inventario</span>
                    </div>

                    <div class="grid gap-3 text-sm">
                        <div class="flex items-start gap-3">
                            <Building2 class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="font-medium text-slate-900">{{ asset.company_name ?? '—' }}</p>
                                <p class="text-slate-500">{{ asset.branch_name }}</p>
                            </div>
                        </div>
                        <div v-if="asset.department_name" class="flex items-start gap-3">
                            <MapPin class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <p class="text-slate-700">{{ asset.department_name }}</p>
                        </div>
                        <div v-if="asset.brand_name || asset.model || asset.serial_number" class="flex items-start gap-3">
                            <Tag class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <div class="text-slate-700">
                                <p v-if="asset.brand_name || asset.model">{{ [asset.brand_name, asset.model].filter(Boolean).join(' · ') }}</p>
                                <p v-if="asset.serial_number" class="text-xs text-slate-500">S/N: {{ asset.serial_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <User class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <p class="text-slate-700">{{ asset.responsible_name ?? 'Sin responsable asignado' }}</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <CalendarCheck class="mt-0.5 size-4 shrink-0 text-slate-400" />
                            <p class="text-slate-700">Última revisión: {{ formatDate(asset.last_reviewed_at) }}</p>
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
