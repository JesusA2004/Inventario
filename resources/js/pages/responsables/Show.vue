<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Boxes, Mail, MapPin, Phone } from '@lucide/vue';
import ResponsibilitiesTab from '@/components/responsibilities/ResponsibilitiesTab.vue';
import type { ResponsibilitySummary } from '@/components/responsibilities/ResponsibilitiesTab.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';

type ResponsibleDetail = {
    id: number;
    full_name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
    active: boolean;
    company: { id: number; name: string } | null;
    branch: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
};

type AssetInCharge = {
    public_id: string;
    internal_code: string;
    name: string;
    asset_type: string | null;
    brand: string | null;
    status: { value: string; label: string; color: string };
};

defineProps<{
    responsible: ResponsibleDetail;
    assets: AssetInCharge[];
    responsibilities: ResponsibilitySummary[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Responsables', href: '/responsables' }] },
});
</script>

<template>
    <Head :title="responsible.full_name" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <h1
                    class="text-2xl font-semibold tracking-tight text-foreground"
                >
                    {{ responsible.full_name }}
                </h1>
                <Badge
                    :variant="responsible.active ? 'default' : 'secondary'"
                    >{{ responsible.active ? 'Activo' : 'Inactivo' }}</Badge
                >
            </div>
            <p
                v-if="responsible.position"
                class="text-sm text-muted-foreground"
            >
                {{ responsible.position }}
            </p>
            <p class="text-sm text-muted-foreground">
                {{ responsible.company?.name }}
                <span v-if="responsible.branch">
                    · {{ responsible.branch.name }}</span
                >
                <span v-if="responsible.department">
                    · {{ responsible.department.name }}</span
                >
            </p>
        </div>

        <div
            class="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-2"
        >
            <div class="flex items-center gap-2 text-sm">
                <Mail class="size-4 text-muted-foreground" />
                <span v-if="responsible.email">{{ responsible.email }}</span>
                <span v-else class="text-muted-foreground"
                    >Sin correo registrado</span
                >
            </div>
            <div class="flex items-center gap-2 text-sm">
                <Phone class="size-4 text-muted-foreground" />
                <span v-if="responsible.phone">{{ responsible.phone }}</span>
                <span v-else class="text-muted-foreground"
                    >Sin teléfono registrado</span
                >
            </div>
        </div>

        <div class="space-y-3">
            <h2
                class="flex items-center gap-2 text-sm font-semibold text-foreground"
            >
                <Boxes class="size-4" /> Equipos actuales
            </h2>
            <div
                v-if="assets.length === 0"
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >
                Este responsable no tiene equipos asignados actualmente.
            </div>
            <div v-else class="grid gap-3 sm:grid-cols-2">
                <Link
                    v-for="asset in assets"
                    :key="asset.public_id"
                    :href="`/activos/${asset.public_id}`"
                    class="rounded-xl border border-border bg-card p-4 transition-colors hover:border-primary/40"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-mono text-xs text-muted-foreground">
                            {{ asset.internal_code }}
                        </p>
                        <StatusBadge
                            :label="asset.status.label"
                            :color="asset.status.color"
                        />
                    </div>
                    <p
                        class="mt-1 flex items-center gap-1 text-sm font-medium text-foreground"
                    >
                        <MapPin class="hidden size-3.5" />
                        {{ asset.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            [asset.asset_type, asset.brand]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                </Link>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-sm font-semibold text-foreground">Responsivas</h2>
            <ResponsibilitiesTab
                :responsibilities="responsibilities"
                context="responsible"
                empty-message="Este responsable todavía no tiene responsivas registradas."
            />
        </div>
    </div>
</template>
