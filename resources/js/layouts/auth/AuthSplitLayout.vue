<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardCheck, QrCode, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

const highlights = [
    { icon: QrCode, text: 'Cada equipo con su propio código QR permanente' },
    { icon: ClipboardCheck, text: 'Auditorías físicas desde el celular' },
    {
        icon: ShieldCheck,
        text: 'Historial completo de responsables y movimientos',
    },
];
</script>

<template>
    <div
        class="relative grid min-h-dvh flex-col items-center justify-center px-6 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col justify-between overflow-hidden bg-sidebar p-10 text-sidebar-foreground lg:flex"
        >
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="
                    background-image: radial-gradient(
                        circle at 1px 1px,
                        white 1px,
                        transparent 0
                    );
                    background-size: 28px 28px;
                "
            />

            <Link
                :href="home()"
                class="relative z-20 flex items-center gap-3 text-lg font-semibold text-white"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-lg bg-sidebar-primary"
                >
                    <AppLogoIcon
                        class="size-5 fill-current text-sidebar-primary-foreground"
                    />
                </span>
                {{ name }}
            </Link>

            <div class="relative z-20 space-y-8">
                <div class="space-y-3">
                    <h2
                        class="text-3xl font-semibold tracking-tight text-white"
                    >
                        Control total de tu inventario de Sistemas
                    </h2>
                    <p class="max-w-md text-sm text-sidebar-foreground/80">
                        Empresas, sucursales, equipos, préstamos y auditorías en
                        un solo lugar, listo para usarse desde computadora o
                        celular.
                    </p>
                </div>

                <ul class="space-y-4">
                    <li
                        v-for="item in highlights"
                        :key="item.text"
                        class="flex items-center gap-3 text-sm text-sidebar-foreground/90"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-md bg-white/10"
                        >
                            <component :is="item.icon" class="size-4" />
                        </span>
                        {{ item.text }}
                    </li>
                </ul>
            </div>

            <p class="relative z-20 text-xs text-sidebar-foreground/50">
                &copy; {{ new Date().getFullYear() }} {{ name }} &mdash;
                Inventario de activos de TI
            </p>
        </div>

        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[360px]"
            >
                <div class="flex flex-col items-center gap-2 lg:hidden">
                    <span
                        class="flex size-10 items-center justify-center rounded-lg bg-primary"
                    >
                        <AppLogoIcon
                            class="size-5 fill-current text-primary-foreground"
                        />
                    </span>
                    <span class="text-base font-semibold">{{ name }}</span>
                </div>

                <div class="flex flex-col space-y-1.5 text-center lg:text-left">
                    <h1
                        v-if="title"
                        class="text-xl font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
