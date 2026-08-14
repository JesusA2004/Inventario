<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MapPin, MoreHorizontal, Plus } from '@lucide/vue';
import { ref } from 'vue';
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
type Department = {
    id: number;
    company_id: number | null;
    company: Company | null;
    name: string;
    code: string | null;
    active: boolean;
    assets_count: number;
};

const props = defineProps<{
    departments: Department[];
    companies: Company[];
    filters: { q?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Áreas / departamentos', href: '/areas' }],
    },
});

const search = ref(props.filters.q ?? '');

function applySearch(value: string) {
    search.value = value;
    router.get(
        '/areas',
        { q: value || undefined },
        { preserveState: true, replace: true },
    );
}

const dialogOpen = ref(false);
const editing = ref<Department | null>(null);

const form = useForm({
    company_id: 'none' as number | string,
    name: '',
    code: '',
    active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(department: Department) {
    editing.value = department;
    form.company_id = department.company_id ?? 'none';
    form.name = department.name;
    form.code = department.code ?? '';
    form.active = department.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };
    form.transform((data) => ({
        ...data,
        company_id: data.company_id === 'none' ? null : data.company_id,
    }));

    if (editing.value) {
        form.put(`/areas/${editing.value.id}`, options);
    } else {
        form.post('/areas', options);
    }
}

function toggleActive(department: Department) {
    router.put(
        `/areas/${department.id}`,
        {
            company_id: department.company_id,
            name: department.name,
            code: department.code,
            active: !department.active,
        },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<Department | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/areas/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Áreas / departamentos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Áreas / departamentos"
            description="Departamentos internos a los que pertenecen los activos"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva área
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar área..."
            @update:search="applySearch"
        />

        <EmptyState
            v-if="departments.length === 0"
            :icon="MapPin"
            title="Todavía no hay áreas registradas"
            description="Crea la primera área para poder asignar activos y responsables."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva área
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
                        <TableHead>Área</TableHead>
                        <TableHead>Empresa</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="department in departments"
                        :key="department.id"
                    >
                        <TableCell class="font-medium text-foreground">{{
                            department.name
                        }}</TableCell>
                        <TableCell>{{
                            department.company?.name ?? 'Todas'
                        }}</TableCell>
                        <TableCell>{{ department.assets_count }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    department.active ? 'default' : 'secondary'
                                "
                            >
                                {{ department.active ? 'Activa' : 'Inactiva' }}
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
                                        @click="openEdit(department)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(department)"
                                    >
                                        {{
                                            department.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = department"
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
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar área' : 'Nueva área'
                }}</DialogTitle>
                <DialogDescription
                    >Puede pertenecer a una empresa específica o estar
                    disponible para todas.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="department-name">Nombre</Label>
                    <Input
                        id="department-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Ej. Sistemas"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="department-company">Empresa (opcional)</Label>
                    <Select v-model="form.company_id">
                        <SelectTrigger id="department-company" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none"
                                >Todas las empresas</SelectItem
                            >
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
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Área activa</p>
                        <p class="text-xs text-muted-foreground">
                            Las áreas inactivas no aparecen en formularios
                            nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear área' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar esta área?"
        description="Esta acción no se puede deshacer. No podrá eliminarse si tiene activos o responsables relacionados."
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
