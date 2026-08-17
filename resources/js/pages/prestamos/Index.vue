<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Plus, UsersRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import Combobox from '@/components/Combobox.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DatePicker from '@/components/DatePicker.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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

type Loan = {
    id: number;
    status: { value: string; label: string; color: string };
    reason: string | null;
    loan_date: string;
    expected_return_date: string | null;
    actual_return_date: string | null;
    asset: { id: number; public_id: string; internal_code: string; name: string } | null;
    company: { id: number; name: string } | null;
    assignedTo: string | null;
    deliveredBy: string | null;
    receivedBy: string | null;
};

const props = defineProps<{
    loans: Paginated<Loan>;
    filters: { q?: string; status?: string; company_id?: string };
    companies: { id: number; name: string }[];
    stats: { active: number; overdue: number };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Préstamos', href: '/prestamos' }] },
});

const search = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? 'all');
const companyFilter = ref(props.filters.company_id ?? 'all');

const companyComboOptions = computed(() => [
    { value: 'all', label: 'Todas las empresas' },
    ...props.companies.map((c) => ({ value: String(c.id), label: c.name })),
]);

function applyFilters() {
    router.get(
        '/prestamos',
        {
            q: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            company_id: companyFilter.value !== 'all' ? companyFilter.value : undefined,
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
    status.value = 'all';
    companyFilter.value = 'all';
    applyFilters();
}

const activeFiltersCount = computed(() => [status.value, companyFilter.value].filter((v) => v !== 'all').length);

function formatDate(value: string | null): string {
    if (!value) {
return '—';
}

    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

const returnDialogLoan = ref<Loan | null>(null);
const returnForm = useForm({
    actual_return_date: new Date().toISOString().slice(0, 10),
    return_notes: '',
});

function openReturnDialog(loan: Loan) {
    returnForm.clearErrors();
    returnForm.reset();
    returnDialogLoan.value = loan;
}

function submitReturn() {
    if (!returnDialogLoan.value) {
return;
}

    returnForm.post(`/prestamos/${returnDialogLoan.value.id}/devolver`, {
        preserveScroll: true,
        onSuccess: () => (returnDialogLoan.value = null),
    });
}

const cancelDialogLoan = ref<Loan | null>(null);
const cancelling = ref(false);

function submitCancel() {
    if (!cancelDialogLoan.value) {
return;
}

    cancelling.value = true;
    router.post(
        `/prestamos/${cancelDialogLoan.value.id}/cancelar`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancelling.value = false;
                cancelDialogLoan.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Préstamos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Préstamos"
            description="Entradas y salidas temporales de equipo"
            help-text="Un préstamo queda vencido cuando pasa la fecha de devolución esperada y sigue en estatus 'Prestado'. Regístralo desde aquí o directamente desde la ficha del activo."
        >
            <template #actions>
                <Link href="/prestamos/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nuevo préstamo
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:w-96">
            <StatCard title="Préstamos activos" :value="stats.active" :icon="UsersRound" />
            <StatCard title="Préstamos vencidos" :value="stats.overdue" tone="destructive" :icon="UsersRound" />
        </div>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por activo..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
            @clear="clearFilters"
        >
            <Select v-model="status" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-40"><SelectValue placeholder="Estatus" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los estatus</SelectItem>
                    <SelectItem value="prestado">Prestado</SelectItem>
                    <SelectItem value="vencido">Vencido</SelectItem>
                    <SelectItem value="devuelto">Devuelto</SelectItem>
                    <SelectItem value="cancelado">Cancelado</SelectItem>
                </SelectContent>
            </Select>
            <Combobox
                v-model="companyFilter"
                :options="companyComboOptions"
                placeholder="Empresa"
                search-placeholder="Buscar empresa..."
                class="w-full lg:w-44"
                @update:model-value="applyFilters"
            />
        </FilterBar>

        <EmptyState
            v-if="loans.data.length === 0"
            :icon="UsersRound"
            title="Todavía no hay préstamos registrados"
            description="Registra la salida de un equipo para llevar el control de préstamos."
        >
            <template #action>
                <Link href="/prestamos/crear">
                    <Button>
                        <Plus class="mr-1 size-4" />
                        Nuevo préstamo
                    </Button>
                </Link>
            </template>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="loan in loans.data" :key="loan.id" class="rounded-xl border border-border bg-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <Link v-if="loan.asset?.public_id" :href="`/activos/${loan.asset.public_id}`" class="font-mono text-sm font-medium hover:underline">
                            {{ loan.asset.internal_code }}
                        </Link>
                        <p v-else class="font-mono text-sm font-medium text-muted-foreground">Activo no disponible</p>
                        <p v-if="loan.asset" class="text-sm text-muted-foreground">{{ loan.asset.name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="loan.status.label" :color="loan.status.color" />
                        <DropdownMenu v-if="loan.status.value === 'prestado' || loan.status.value === 'vencido'">
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon" class="size-8">
                                    <MoreHorizontal class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="openReturnDialog(loan)">Registrar devolución</DropdownMenuItem>
                                <DropdownMenuItem class="text-destructive focus:text-destructive" @click="cancelDialogLoan = loan">
                                    Cancelar préstamo
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-muted-foreground">Asignado a</p>
                        <p>{{ loan.assignedTo ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Salida</p>
                        <p>{{ formatDate(loan.loan_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Devolución esperada</p>
                        <p>{{ formatDate(loan.expected_return_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Devolución real</p>
                        <p>{{ formatDate(loan.actual_return_date) }}</p>
                    </div>
                </div>
                <p v-if="loan.reason" class="mt-2 text-sm text-muted-foreground">{{ loan.reason }}</p>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">{{ loans.total }} préstamos</p>
                <Pager :links="loans.links" />
            </div>
        </div>
    </div>

    <Dialog :open="!!returnDialogLoan" @update:open="(value) => !value && (returnDialogLoan = null)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Registrar devolución</DialogTitle>
                <DialogDescription v-if="returnDialogLoan">
                    Salió el {{ formatDate(returnDialogLoan.loan_date) }}. La fecha de devolución debe ser igual o posterior a esa fecha.
                </DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitReturn">
                <div class="grid gap-2">
                    <Label>Fecha de devolución</Label>
                    <DatePicker v-model="returnForm.actual_return_date" from-today />
                    <InputError :message="returnForm.errors.actual_return_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="return-notes">Observaciones (opcional)</Label>
                    <Textarea id="return-notes" v-model="returnForm.return_notes" rows="2" />
                    <InputError :message="returnForm.errors.return_notes" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="returnForm.processing">
                        <Spinner v-if="returnForm.processing" class="mr-1" />
                        Confirmar devolución
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!cancelDialogLoan"
        title="¿Cancelar este préstamo?"
        description="El préstamo se marcará como cancelado y dejará de contarse como activo."
        confirm-text="Cancelar préstamo"
        destructive
        :loading="cancelling"
        @update:open="(value) => !value && (cancelDialogLoan = null)"
        @confirm="submitCancel"
    />
</template>
