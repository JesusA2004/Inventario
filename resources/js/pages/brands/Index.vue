<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Plus, Tag } from '@lucide/vue';
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

type Brand = {
    id: number;
    name: string;
    active: boolean;
    assets_count: number;
    parts_count: number;
};

const props = defineProps<{
    brands: Brand[];
    filters: { q?: string };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Marcas', href: '/marcas' }] },
});

const search = ref(props.filters.q ?? '');

function applySearch(value: string) {
    search.value = value;
    router.get(
        '/marcas',
        { q: value || undefined },
        { preserveState: true, replace: true },
    );
}

const dialogOpen = ref(false);
const editing = ref<Brand | null>(null);
const form = useForm({ name: '', active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(brand: Brand) {
    editing.value = brand;
    form.name = brand.name;
    form.active = brand.active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(`/marcas/${editing.value.id}`, options);
    } else {
        form.post('/marcas', options);
    }
}

function toggleActive(brand: Brand) {
    router.put(
        `/marcas/${brand.id}`,
        { name: brand.name, active: !brand.active },
        { preserveScroll: true },
    );
}

const confirmDelete = ref<Brand | null>(null);
const deleting = ref(false);

function destroy() {
    if (!confirmDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/marcas/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Marcas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Marcas"
            description="Fabricantes de los equipos registrados en el inventario"
        >
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva marca
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :search="search"
            search-placeholder="Buscar marca..."
            @update:search="applySearch"
        />

        <EmptyState
            v-if="brands.length === 0"
            :icon="Tag"
            title="Todavía no hay marcas registradas"
            description="Crea la primera marca para poder capturar activos."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nueva marca
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
                        <TableHead>Marca</TableHead>
                        <TableHead>Activos</TableHead>
                        <TableHead>Piezas</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="brand in brands" :key="brand.id">
                        <TableCell class="font-medium text-foreground">{{
                            brand.name
                        }}</TableCell>
                        <TableCell>{{ brand.assets_count }}</TableCell>
                        <TableCell>{{ brand.parts_count }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    brand.active ? 'default' : 'secondary'
                                "
                            >
                                {{ brand.active ? 'Activa' : 'Inactiva' }}
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
                                    <DropdownMenuItem @click="openEdit(brand)"
                                        >Editar</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        @click="toggleActive(brand)"
                                    >
                                        {{
                                            brand.active
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete = brand"
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
                    editing ? 'Editar marca' : 'Nueva marca'
                }}</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="brand-name">Nombre</Label>
                    <Input
                        id="brand-name"
                        v-model="form.name"
                        autofocus
                        placeholder="Ej. HP"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div
                    class="flex items-center justify-between rounded-lg border border-border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Marca activa</p>
                        <p class="text-xs text-muted-foreground">
                            Las marcas inactivas no aparecen en formularios
                            nuevos.
                        </p>
                    </div>
                    <Switch v-model="form.active" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear marca' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!confirmDelete"
        title="¿Eliminar esta marca?"
        description="Esta acción no se puede deshacer. No podrá eliminarse si tiene activos o piezas relacionadas."
        confirm-text="Eliminar"
        destructive
        :loading="deleting"
        @update:open="(value) => !value && (confirmDelete = null)"
        @confirm="destroy"
    />
</template>
