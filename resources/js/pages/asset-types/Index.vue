<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Plus, Shapes } from '@lucide/vue';
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

type AssetType = {
    id: number;
    name: string;
    code: string;
    icon: string | null;
    active: boolean;
    assets_count: number;
};

const props = defineProps<{
    assetTypes: AssetType[];
    filters: { q?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tipos de activo', href: '/tipos-activo' }],
    },
});

const search = ref(props.filters.q ?? '');

function applySearch(value: string) {
    search.value = value;
    router.get(
        '/tipos-activo',
        { q: value || undefined },
        { preserveState: true, replace: true },
    );
}

const dialogOpen = ref(false);
const editing = ref<AssetType | null>(null);
const form = useForm({ name: '', code: '', icon: '', active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(assetType: AssetType) {
    editing.value = assetType;
    form.name = assetType.name;
    form.code = assetType.code;
    form.icon = assetType.icon ?? '';
    form.active = assetType.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(`/tipos-activo/${editing.value.id}`, options);
    } else {
        form.post('/tipos-activo', options);
    }
}

function toggleActive(assetType: AssetType) {
    router.put(
        `/tipos-activo/${assetType.id}`,
        {
            name: assetType.name,
            code: assetType.code,
            icon: assetType.icon,
            active: !assetType.active,
        },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<AssetType | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/tipos-activo/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Tipos de activo" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Tipos de activo"
            description="Categorías de equipo utilizadas al registrar un activo"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo tipo
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar tipo..."
            @update:search="applySearch"
        />

        <EmptyState
            v-if="assetTypes.length === 0"
            :icon="Shapes"
            title="Todavía no hay tipos de activo"
            description="Crea el primer tipo (por ejemplo, Laptop o Monitor) para poder registrar equipos."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo tipo
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
                        <TableHead>Tipo</TableHead>
                        <TableHead>Código</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="assetType in assetTypes"
                        :key="assetType.id"
                    >
                        <TableCell class="font-medium text-foreground">{{
                            assetType.name
                        }}</TableCell>
                        <TableCell class="font-mono text-sm">{{
                            assetType.code
                        }}</TableCell>
                        <TableCell>{{ assetType.assets_count }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    assetType.active ? 'default' : 'secondary'
                                "
                            >
                                {{ assetType.active ? 'Activo' : 'Inactivo' }}
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
                                        @click="openEdit(assetType)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(assetType)"
                                    >
                                        {{
                                            assetType.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = assetType"
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
                    editing ? 'Editar tipo de activo' : 'Nuevo tipo de activo'
                }}</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="type-name">Nombre</Label>
                    <Input
                        id="type-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Ej. Laptop"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="type-code">Código corto</Label>
                    <Input
                        id="type-code"
                        v-model="form.code"
                        maxlength="10"
                        class="uppercase"
                        placeholder="Ej. LAP"
                    />
                    <InputError :message="form.errors.code" />
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Tipo activo</p>
                        <p class="text-xs text-muted-foreground">
                            Los tipos inactivos no aparecen en formularios
                            nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear tipo' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar este tipo de activo?"
        description="Esta acción no se puede deshacer. No podrá eliminarse si tiene activos relacionados."
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
