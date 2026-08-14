<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Plus, Users } from '@lucide/vue';
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

type Company = { id: number; name: string; code: string };
type Branch = { id: number; name: string; company_id: number };
type Department = { id: number; name: string; company_id: number | null };
type Responsible = {
    id: number;
    company_id: number;
    company: Company;
    branch_id: number | null;
    branch: Branch | null;
    department_id: number | null;
    department: Department | null;
    full_name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
    active: boolean;
    assets_in_charge_count: number;
};

const props = defineProps<{
    responsiblePeople: Responsible[];
    companies: Company[];
    branches: Branch[];
    departments: Department[];
    filters: { q?: string; company_id?: string };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Responsables', href: '/responsables' }] },
});

const search = ref(props.filters.q ?? '');
const companyFilter = ref(props.filters.company_id ?? 'all');

function applyFilters() {
    router.get(
        '/responsables',
        {
            q: search.value || undefined,
            company_id:
                companyFilter.value !== 'all' ? companyFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function applySearch(value: string) {
    search.value = value;
    applyFilters();
}

const activeFiltersCount = computed(() =>
    companyFilter.value !== 'all' ? 1 : 0,
);

const dialogOpen = ref(false);
const editing = ref<Responsible | null>(null);

const form = useForm({
    company_id: '' as number | string,
    branch_id: 'none' as number | string,
    department_id: 'none' as number | string,
    full_name: '',
    position: '',
    email: '',
    phone: '',
    notes: '',
    active: true,
});

const branchesForCompany = computed(() =>
    props.branches.filter(
        (branch) => String(branch.company_id) === String(form.company_id),
    ),
);
const departmentsForCompany = computed(() =>
    props.departments.filter(
        (department) =>
            !department.company_id ||
            String(department.company_id) === String(form.company_id),
    ),
);

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(responsible: Responsible) {
    editing.value = responsible;
    form.company_id = responsible.company_id;
    form.branch_id = responsible.branch_id ?? 'none';
    form.department_id = responsible.department_id ?? 'none';
    form.full_name = responsible.full_name;
    form.position = responsible.position ?? '';
    form.email = responsible.email ?? '';
    form.phone = responsible.phone ?? '';
    form.active = responsible.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    form.transform((data) => ({
        ...data,
        branch_id: data.branch_id === 'none' ? null : data.branch_id,
        department_id:
            data.department_id === 'none' ? null : data.department_id,
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(`/responsables/${editing.value.id}`, options);
    } else {
        form.post('/responsables', options);
    }
}

function toggleActive(responsible: Responsible) {
    router.put(
        `/responsables/${responsible.id}`,
        {
            company_id: responsible.company_id,
            branch_id: responsible.branch_id,
            department_id: responsible.department_id,
            full_name: responsible.full_name,
            position: responsible.position,
            email: responsible.email,
            phone: responsible.phone,
            active: !responsible.active,
        },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<Responsible | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/responsables/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Responsables" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Responsables"
            description="Personas que pueden tener equipo asignado"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo responsable
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por nombre o correo..."
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
            v-if="responsiblePeople.length === 0"
            :icon="Users"
            title="Todavía no hay responsables registrados"
            description="Crea el primer responsable para poder asignarle activos."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo responsable
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
                        <TableHead>Responsable</TableHead>
                        <TableHead>Empresa</TableHead>
                        <TableHead>Sucursal / área</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="responsible in responsiblePeople"
                        :key="responsible.id"
                    >
                        <TableCell>
                            <p class="font-medium text-foreground">
                                {{ responsible.full_name }}
                            </p>
                            <p
                                v-if="responsible.position"
                                class="text-xs text-muted-foreground"
                            >
                                {{ responsible.position }}
                            </p>
                        </TableCell>
                        <TableCell>{{ responsible.company.name }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{
                                [
                                    responsible.branch?.name,
                                    responsible.department?.name,
                                ]
                                    .filter(Boolean)
                                    .join(' · ') || '—'
                            }}
                        </TableCell>
                        <TableCell>{{
                            responsible.assets_in_charge_count
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    responsible.active ? 'default' : 'secondary'
                                "
                            >
                                {{ responsible.active ? 'Activo' : 'Inactivo' }}
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
                                    <DropdownMenuItem
                                        @click="openEdit(responsible)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(responsible)"
                                    >
                                        {{
                                            responsible.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = responsible"
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
                    editing ? 'Editar responsable' : 'Nuevo responsable'
                }}</DialogTitle>
                <DialogDescription
                    >Un responsable no necesita ser un usuario del
                    sistema.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="resp-name">Nombre completo</Label>
                    <Input id="resp-name" v-model="form.full_name" autofocus />
                    <InputError :message="form.errors.full_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="resp-company">Empresa</Label>
                    <Select v-model="form.company_id">
                        <SelectTrigger id="resp-company" class="w-full">
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
                        <Label for="resp-branch">Sucursal (opcional)</Label>
                        <Select v-model="form.branch_id">
                            <SelectTrigger id="resp-branch" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >Sin sucursal</SelectItem
                                >
                                <SelectItem
                                    v-for="branch in branchesForCompany"
                                    :key="branch.id"
                                    :value="String(branch.id)"
                                >
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="resp-department">Área (opcional)</Label>
                        <Select v-model="form.department_id">
                            <SelectTrigger id="resp-department" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Sin área</SelectItem>
                                <SelectItem
                                    v-for="department in departmentsForCompany"
                                    :key="department.id"
                                    :value="String(department.id)"
                                >
                                    {{ department.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="resp-position">Puesto</Label>
                        <Input
                            id="resp-position"
                            v-model="form.position"
                            placeholder="Opcional"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="resp-phone">Teléfono</Label>
                        <Input
                            id="resp-phone"
                            v-model="form.phone"
                            placeholder="Opcional"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="resp-email">Correo</Label>
                    <Input
                        id="resp-email"
                        v-model="form.email"
                        type="email"
                        placeholder="Opcional"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Responsable activo</p>
                        <p class="text-xs text-muted-foreground">
                            Los responsables inactivos no aparecen en
                            formularios nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear responsable' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar este responsable?"
        description="Esta acción no se puede deshacer. No podrá eliminarse si tiene activos asignados."
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
