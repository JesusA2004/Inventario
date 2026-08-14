<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Plus, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/FilterBar.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pager from '@/components/Pager.vue';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated } from '@/types/assets';

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    email_verified_at: string | null;
    roles: string[];
};

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { q?: string };
    roles: string[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Usuarios', href: '/usuarios' }] },
});

const search = ref(props.filters.q ?? '');

function applySearch(value: string) {
    search.value = value;
    router.get('/usuarios', { q: value || undefined }, { preserveState: true, replace: true });
}

const roleLabels: Record<string, string> = {
    superadministrador: 'Superadministrador',
    sistemas: 'Sistemas',
    auditor: 'Auditor',
    consulta: 'Consulta',
};

const dialogOpen = ref(false);
const editing = ref<UserRow | null>(null);
const form = useForm({ name: '', email: '', role: 'consulta' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(user: UserRow) {
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles[0] ?? 'consulta';
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    if (editing.value) {
        form.put(`/usuarios/${editing.value.id}`, options);
    } else {
        form.post('/usuarios', options);
    }
}

function toggleActive(user: UserRow) {
    router.post(`/usuarios/${user.id}/estado`, {}, { preserveScroll: true });
}

function resetPassword(user: UserRow) {
    router.post(`/usuarios/${user.id}/restablecer-contrasena`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Usuarios" description="Cuentas con acceso al sistema">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo usuario
                </Button>
            </template>
        </PageHeader>

        <FilterBar :search="search" search-placeholder="Buscar por nombre o correo..." @update:search="applySearch" />

        <EmptyState
            v-if="users.data.length === 0"
            :icon="ShieldCheck"
            title="Todavía no hay usuarios"
            description="Crea el primer usuario para dar acceso al sistema."
        >
            <template #action>
                <Button @click="openCreate">
                    <Plus class="mr-1 size-4" />
                    Nuevo usuario
                </Button>
            </template>
        </EmptyState>

        <template v-else>
            <div class="overflow-x-auto rounded-xl border border-border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Usuario</TableHead>
                            <TableHead>Rol</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell>
                                <p class="font-medium text-foreground">{{ user.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ roleLabels[user.roles[0]] ?? user.roles[0] ?? 'Sin rol' }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="size-8"><MoreHorizontal class="size-4" /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openEdit(user)">Editar</DropdownMenuItem>
                                        <DropdownMenuItem @click="resetPassword(user)">Restablecer contraseña</DropdownMenuItem>
                                        <DropdownMenuItem @click="toggleActive(user)">
                                            {{ user.is_active ? 'Desactivar' : 'Activar' }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pager :links="users.links" />
        </template>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>{{ editing ? 'Editar usuario' : 'Nuevo usuario' }}</DialogTitle>
                <DialogDescription v-if="!editing">Se generará una contraseña temporal que se mostrará una sola vez.</DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="user-name">Nombre</Label>
                    <Input id="user-name" v-model="form.name" autofocus />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="user-email">Correo electrónico</Label>
                    <Input id="user-email" v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label>Rol</Label>
                    <Select v-model="form.role">
                        <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] ?? role }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-1" />
                        {{ editing ? 'Guardar cambios' : 'Crear usuario' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
