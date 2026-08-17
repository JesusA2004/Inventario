<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Package, Plus, QrCode } from '@lucide/vue';
import { computed, ref } from 'vue';
import Combobox from '@/components/Combobox.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Paginated } from '@/types/assets';

type Part = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    part_number: string | null;
    status: { value: string; label: string; color: string };
    in_inventory: boolean;
    assembled: boolean;
    quantity: number;
    needs_label: boolean;
    company: { id: number; name: string } | null;
    branch: { id: number; name: string } | null;
    brand: { id: number; name: string } | null;
    relatedAsset: { id: number; public_id: string; internal_code: string } | null;
};

const props = defineProps<{
    parts: Paginated<Part>;
    filters: { q?: string; company_id?: string; status?: string };
    filterOptions: {
        companies: { id: number; name: string }[];
        statuses: { value: string; label: string; color: string }[];
    };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Piezas y refacciones', href: '/piezas' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');

const companyComboOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.filterOptions.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);

function applyFilters() {
    router.get(
        '/piezas',
        {
            q: search.value || undefined,
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

function clearFilters() {
    search.value = '';
    companyFilter.value = 'all';
    statusFilter.value = 'all';
    applyFilters();
}

const activeFiltersCount = computed(
    () => [companyFilter.value, statusFilter.value].filter((v) => v !== 'all').length,
);

const decommissionPart = ref<Part | null>(null);
const decommissionForm = useForm({ reason: '', notes: '' });

function submitDecommission() {
    if (!decommissionPart.value) {
return;
}

    decommissionForm.post(`/piezas/${decommissionPart.value.public_id}/baja`, {
        preserveScroll: true,
        onSuccess: () => (decommissionPart.value = null),
    });
}
</script>

<template>
    <Head title="Piezas y refacciones" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Piezas y refacciones"
            description="Componentes en almacén o ensamblados en un activo"
            help-text="'Ensamblada' significa que la pieza forma parte física de un activo (RAM, SSD, cargador...) y aparece en su pestaña Piezas. Si no está ensamblada, existe suelta en almacén."
        >
            <template #actions>
                <Link href="/piezas/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nueva pieza
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por clave, nombre o número de parte..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Combobox
                v-model="companyFilter"
                :options="companyComboOptions"
                placeholder="Empresa"
                search-placeholder="Buscar empresa..."
                class="w-full lg:w-44"
                @update:model-value="applyFilters"
            />
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-40"><SelectValue placeholder="Estatus" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los estatus</SelectItem>
                    <SelectItem v-for="s in filterOptions.statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                </SelectContent>
            </Select>
        </FilterBar>

        <EmptyState
            v-if="parts.data.length === 0"
            :icon="Package"
            title="Todavía no hay piezas registradas"
            description="Registra la primera pieza o refacción de tu almacén."
        >
            <template #action>
                <Link href="/piezas/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nueva pieza
                    </Button>
                </Link>
            </template>
        </EmptyState>

        <template v-else>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="part in parts.data" :key="part.id" class="rounded-xl border border-border bg-card p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-foreground">{{ part.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">{{ part.internal_code }}</p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon" class="size-8 shrink-0"><MoreHorizontal class="size-4" /></Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem as-child>
                                    <Link :href="`/piezas/${part.public_id}/editar`">Editar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="part.needs_label" as-child>
                                    <a :href="`/piezas/${part.public_id}/qr/descargar`">Descargar QR</a>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="part.in_inventory"
                                    class="text-destructive focus:text-destructive"
                                    @click="decommissionPart = part"
                                >
                                    Dar de baja
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <StatusBadge :label="part.status.label" :color="part.status.color" />
                        <Badge v-if="part.assembled" variant="outline">Ensamblada</Badge>
                        <Badge v-if="!part.in_inventory" variant="secondary">Fuera de inventario</Badge>
                        <Badge v-if="part.needs_label" variant="outline"><QrCode class="mr-1 size-3" />QR</Badge>
                    </div>

                    <div class="mt-3 space-y-1 text-sm text-muted-foreground">
                        <p v-if="part.company">{{ part.company.name }} <span v-if="part.branch"> · {{ part.branch.name }}</span></p>
                        <p v-if="part.brand">{{ part.brand.name }}</p>
                        <p v-if="part.relatedAsset?.public_id">
                            Vinculada a
                            <Link :href="`/activos/${part.relatedAsset.public_id}`" class="font-mono hover:underline">
                                {{ part.relatedAsset.internal_code }}
                            </Link>
                        </p>
                        <p>Cantidad: {{ part.quantity }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">{{ parts.total }} piezas</p>
                <Pager :links="parts.links" />
            </div>
        </template>
    </div>

    <Dialog :open="!!decommissionPart" @update:open="(value) => !value && (decommissionPart = null)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Dar de baja esta pieza</DialogTitle>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitDecommission">
                <div class="grid gap-2">
                    <Label for="part-reason">Motivo</Label>
                    <Textarea id="part-reason" v-model="decommissionForm.reason" rows="2" required />
                    <InputError :message="decommissionForm.errors.reason" />
                </div>
                <div class="grid gap-2">
                    <Label for="part-decommission-notes">Observaciones (opcional)</Label>
                    <Textarea id="part-decommission-notes" v-model="decommissionForm.notes" rows="2" />
                    <InputError :message="decommissionForm.errors.notes" />
                </div>
                <DialogFooter>
                    <Button type="submit" variant="destructive" :disabled="decommissionForm.processing">
                        <Spinner v-if="decommissionForm.processing" class="mr-1" />
                        Dar de baja
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
