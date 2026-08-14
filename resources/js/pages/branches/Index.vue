<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Building2, MoreHorizontal, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

type Company = { id: number; name: string; code: string };
type Branch = {
    id: number;
    company_id: number;
    company: Company;
    name: string;
    code: string;
    address: string | null;
    city: string | null;
    state: string | null;
    phone: string | null;
    notes: string | null;
    active: boolean;
    assets_count: number;
};

const props = defineProps<{
    branches: Branch[];
    companies: Company[];
    filters: { q?: string; company_id?: string };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Sucursales', href: '/sucursales' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');

function applyFilters() {
    router.get(
        '/sucursales',
        {
            q: search.value || undefined,
            company_id:
                companyFilter.value && companyFilter.value !== 'all'
                    ? companyFilter.value
                    : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

const activeFiltersCount = computed(() =>
    companyFilter.value && companyFilter.value !== 'all' ? 1 : 0,
);

const dialogOpen = ref(false);
const editing = ref<Branch | null>(null);

const form = useForm({
    company_id: '' as number | string,
    name: '',
    code: '',
    address: '',
    city: '',
    state: '',
    phone: '',
    notes: '',
    active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(branch: Branch) {
    editing.value = branch;
    form.company_id = branch.company_id;
    form.name = branch.name;
    form.code = branch.code;
    form.address = branch.address ?? '';
    form.city = branch.city ?? '';
    form.state = branch.state ?? '';
    form.phone = branch.phone ?? '';
    form.notes = branch.notes ?? '';
    form.active = branch.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(`/sucursales/${editing.value.id}`, options);
    } else {
        form.post('/sucursales', options);
    }
}

function toggleActive(branch: Branch) {
    router.put(
        `/sucursales/${branch.id}`,
        {
            company_id: branch.company_id,
            name: branch.name,
            code: branch.code,
            address: branch.address,
            city: branch.city,
            state: branch.state,
            phone: branch.phone,
            notes: branch.notes,
            active: !branch.active,
        },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<Branch | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/sucursales/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Sucursales" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Sucursales"
            description="Ubicaciones físicas de cada empresa"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva sucursal
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por nombre o código..."
            :active-filters-count="activeFiltersCount"
            @update:search="applySearch"
        >
            <Select v-model="companyFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-full lg:w-48">
                    <SelectValue placeholder="Todas las empresas" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas las empresas</SelectItem>
                    <SelectItem
                        v-for="company in companies"
                        :key="company.id"
                        :value="String(company.id)"
                    >
                        {{ company.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </FilterBar>

        <EmptyState
            v-if="branches.length === 0"
            :icon="Building2"
            title="Todavía no hay sucursales registradas"
            description="Crea la primera sucursal para poder ubicar activos y responsables."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva sucursal
                </Button>
            </template>
        </EmptyState>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-border bg-card"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Sucursal</TableHead>
                        <TableHead>Empresa</TableHead>
                        <TableHead>Ciudad</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="branch in branches" :key="branch.id">
                        <TableCell>
                            <p class="font-medium text-foreground">
                                {{ branch.name }}
                            </p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ branch.code }}
                            </p>
                        </TableCell>
                        <TableCell>{{ branch.company.name }}</TableCell>
                        <TableCell>{{ branch.city ?? '—' }}</TableCell>
                        <TableCell>{{ branch.assets_count }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    branch.active ? 'default' : 'secondary'
                                "
                            >
                                {{ branch.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                    >
                                        <MoreHorizontal class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="openEdit(branch)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(branch)"
                                    >
                                        {{
                                            branch.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = branch"
                                    >
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar sucursal' : 'Nueva sucursal'
                }}</DialogTitle>
                <DialogDescription
                    >Cada sucursal pertenece a una sola
                    empresa.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="branch-company">Empresa</Label>
                    <Select v-model="form.company_id">
                        <SelectTrigger id="branch-company" class="w-full">
                            <SelectValue placeholder="Selecciona una empresa" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="company in companies"
                                :key="company.id"
                                :value="String(company.id)"
                            >
                                {{ company.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.company_id" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="branch-name">Nombre</Label>
                        <Input
                            id="branch-name"
                            v-model="form.name"
                            placeholder="Ej. Corporativo"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="branch-code">Código</Label>
                        <Input
                            id="branch-code"
                            v-model="form.code"
                            placeholder="Ej. CORP"
                        />
                        <InputError :message="form.errors.code" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="branch-address">Dirección (opcional)</Label>
                    <Input id="branch-address" v-model="form.address" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="grid gap-2">
                        <Label for="branch-city">Ciudad</Label>
                        <Input id="branch-city" v-model="form.city" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="branch-state">Estado</Label>
                        <Input id="branch-state" v-model="form.state" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="branch-phone">Teléfono</Label>
                        <Input id="branch-phone" v-model="form.phone" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="branch-notes">Observaciones</Label>
                    <Textarea id="branch-notes" v-model="form.notes" rows="2" />
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Sucursal activa</p>
                        <p class="text-xs text-muted-foreground">
                            Las sucursales inactivas no aparecen en formularios
                            nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear sucursal' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar esta sucursal?"
        description="Esta acción no se puede deshacer. No podrá eliminarse si tiene activos o responsables relacionados."
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
