<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Building2, MoreHorizontal, Plus } from '@lucide/vue';
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

type Company = {
    id: number;
    name: string;
    legal_name: string | null;
    code: string;
    logo_path: string | null;
    color: string | null;
    active: boolean;
    branches_count: number;
    assets_count: number;
};

const props = defineProps<{
    companies: Company[];
    filters: { q?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Empresas', href: '/empresas' }],
    },
});

const search = ref(props.filters.q ?? '');

function applySearch(value: string) {
    search.value = value;
    router.get(
        '/empresas',
        { q: value || undefined },
        { preserveState: true, replace: true },
    );
}

const dialogOpen = ref(false);
const editing = ref<Company | null>(null);

const form = useForm({
    name: '',
    legal_name: '',
    code: '',
    color: '#0f2a4a',
    active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(company: Company) {
    editing.value = company;
    form.name = company.name;
    form.legal_name = company.legal_name ?? '';
    form.code = company.code;
    form.color = company.color ?? '#0f2a4a';
    form.active = company.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/empresas/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post('/empresas', {
            preserveScroll: true,
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

function toggleActive(company: Company) {
    router.put(
        `/empresas/${company.id}`,
        {
            name: company.name,
            legal_name: company.legal_name,
            code: company.code,
            color: company.color,
            active: !company.active,
        },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<Company | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/empresas/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Empresas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Empresas"
            description="Empresas que participan en el inventario de TI"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva empresa
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar por nombre o código..."
            @update:search="applySearch"
        />

        <EmptyState
            v-if="companies.length === 0"
            :icon="Building2"
            title="Todavía no hay empresas registradas"
            description="Empieza creando la primera empresa para poder capturar sucursales y activos."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva empresa
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
                        <TableHead>Empresa</TableHead>
                        <TableHead>Código</TableHead>
                        <TableHead>Sucursales</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="company in companies"
                        :key="company.id"
                        class="transition-colors"
                    >
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-md text-xs font-semibold text-white"
                                    :style="{
                                        backgroundColor:
                                            company.color ?? '#0f2a4a',
                                    }"
                                >
                                    {{ company.code.slice(0, 2) }}
                                </span>
                                <div>
                                    <p class="font-medium text-foreground">
                                        {{ company.name }}
                                    </p>
                                    <p
                                        v-if="company.legal_name"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ company.legal_name }}
                                    </p>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            company.code
                        }}</TableCell>
                        <TableCell>{{ company.branches_count }}</TableCell>
                        <TableCell>{{ company.assets_count }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    company.active ? 'default' : 'secondary'
                                "
                            >
                                {{ company.active ? 'Activa' : 'Inactiva' }}
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
                                    <DropdownMenuItem @click="openEdit(company)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(company)"
                                    >
                                        {{
                                            company.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = company"
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
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar empresa' : 'Nueva empresa'
                }}</DialogTitle>
                <DialogDescription>
                    El código se usará para generar claves internas, por ejemplo
                    CML-EC-001.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="company-name">Nombre</Label>
                    <Input
                        id="company-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Ej. MR LANA"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="company-legal-name"
                        >Razón social (opcional)</Label
                    >
                    <Input id="company-legal-name" v-model="form.legal_name" />
                    <InputError :message="form.errors.legal_name" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="company-code">Código corto</Label>
                        <Input
                            id="company-code"
                            v-model="form.code"
                            maxlength="10"
                            class="uppercase"
                            placeholder="Ej. CML"
                        />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company-color">Color identificador</Label>
                        <Input
                            id="company-color"
                            v-model="form.color"
                            type="color"
                            class="h-9 p-1"
                        />
                        <InputError :message="form.errors.color" />
                    </div>
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Empresa activa</p>
                        <p class="text-xs text-muted-foreground">
                            Las empresas inactivas no aparecen en los
                            formularios nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear empresa' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar esta empresa?"
        :description="`Esta acción no se puede deshacer. Si ${confirmDelete?.name} tiene sucursales o activos relacionados, no podrá eliminarse.`"
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
