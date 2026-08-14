<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Boxes,
    Building2,
    ClipboardCheck,
    FileBarChart,
    Landmark,
    LayoutGrid,
    MapPin,
    Package,
    ScanLine,
    Settings,
    Shapes,
    ShieldCheck,
    Tag,
    Users,
    UsersRound,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavGroup } from '@/types';

const navGroups: NavGroup[] = [
    {
        title: 'Inicio',
        items: [{ title: 'Panel', href: dashboard(), icon: LayoutGrid }],
    },
    {
        title: 'Inventario',
        items: [
            { title: 'Activos', href: '/activos', icon: Boxes },
            { title: 'Piezas y refacciones', href: '/piezas', icon: Package },
            { title: 'Préstamos', href: '/prestamos', icon: UsersRound },
        ],
    },
    {
        title: 'Organización',
        items: [
            { title: 'Empresas', href: '/empresas', icon: Landmark },
            { title: 'Sucursales', href: '/sucursales', icon: Building2 },
            { title: 'Áreas / departamentos', href: '/areas', icon: MapPin },
            { title: 'Responsables', href: '/responsables', icon: Users },
        ],
    },
    {
        title: 'Catálogos',
        items: [
            { title: 'Tipos de activo', href: '/tipos-activo', icon: Shapes },
            { title: 'Marcas', href: '/marcas', icon: Tag },
        ],
    },
    {
        title: 'Control',
        items: [
            { title: 'Auditorías', href: '/auditorias', icon: ClipboardCheck },
            { title: 'Escanear QR', href: '/escanear', icon: ScanLine },
            { title: 'Reportes', href: '/reportes', icon: FileBarChart },
        ],
    },
    {
        title: 'Administración',
        items: [
            { title: 'Usuarios', href: '/usuarios', icon: ShieldCheck },
            { title: 'Roles y permisos', href: '/roles', icon: Users },
            { title: 'Configuración', href: '/configuracion', icon: Settings },
        ],
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="group in navGroups"
                :key="group.title"
                :title="group.title"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
