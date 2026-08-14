<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Role = {
    name: string;
    label: string;
    permissions: string[];
    users_count: number;
};

defineProps<{
    roles: Role[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Roles y permisos', href: '/roles' }] },
});

const permissionLabels: Record<string, string> = {
    'ver-activos': 'Ver activos',
    'crear-activos': 'Crear activos',
    'editar-activos': 'Editar activos',
    'dar-de-baja-activos': 'Dar de baja activos',
    'ver-piezas': 'Ver piezas',
    'gestionar-piezas': 'Gestionar piezas',
    'ver-prestamos': 'Ver préstamos',
    'gestionar-prestamos': 'Gestionar préstamos',
    'ver-auditorias': 'Ver auditorías',
    'gestionar-auditorias': 'Gestionar auditorías',
    'ver-reportes': 'Ver reportes',
    'ver-catalogos': 'Ver catálogos',
    'gestionar-catalogos': 'Gestionar catálogos',
    'gestionar-usuarios': 'Gestionar usuarios',
    'gestionar-configuracion': 'Gestionar configuración',
};
</script>

<template>
    <Head title="Roles y permisos" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Roles y permisos" description="Los roles determinan qué puede hacer cada usuario en el sistema" />

        <div class="grid gap-4 sm:grid-cols-2">
            <Card v-for="role in roles" :key="role.name">
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <ShieldCheck class="size-4 text-primary" />
                        {{ role.label }}
                    </CardTitle>
                    <Badge variant="outline">{{ role.users_count }} usuario(s)</Badge>
                </CardHeader>
                <CardContent>
                    <div v-if="role.name === 'superadministrador'" class="text-sm text-muted-foreground">
                        Acceso total a todos los módulos del sistema.
                    </div>
                    <div v-else class="flex flex-wrap gap-1.5">
                        <Badge v-for="permission in role.permissions" :key="permission" variant="secondary" class="font-normal">
                            {{ permissionLabels[permission] ?? permission }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
