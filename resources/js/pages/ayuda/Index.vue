<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Ban,
    Boxes,
    Camera,
    ClipboardCheck,
    FileBarChart,
    Landmark,
    Layers,
    MapPin,
    Package,
    Printer,
    QrCode,
    ScanLine,
    SlidersHorizontal,
    Tags,
    UserCog,
    Users,
    UsersRound,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Ayuda', href: '/ayuda' }] },
});

const steps = [
    { title: 'Crea una empresa', description: 'Da de alta la empresa dueña de los equipos, en Organización → Empresas.', icon: Landmark },
    { title: 'Crea una sucursal', description: 'Cada empresa puede tener varias ubicaciones físicas, en Organización → Sucursales.', icon: MapPin },
    { title: 'Crea un área', description: 'Opcional: divide una sucursal en departamentos/áreas internas.', icon: MapPin },
    { title: 'Crea un responsable', description: 'Registra a la persona que tendrá activos asignados, en Organización → Responsables.', icon: Users },
    { title: 'Registra un activo', description: 'Da de alta el equipo con su clave, tipo, marca, serie y ubicación, en Activos → Nuevo activo.', icon: Boxes },
    { title: 'Toma o sube una foto', description: 'Desde la ficha del activo, pestaña Archivos, usa la cámara del teléfono o sube un archivo.', icon: Camera },
    { title: 'Genera e imprime el QR', description: 'Desde Etiquetas QR o la pestaña QR del activo: elige el tamaño de etiqueta y descarga el PDF.', icon: QrCode },
    { title: 'Escanea el QR', description: 'Usa Escanear QR para consultar un activo o registrar hallazgos durante una auditoría.', icon: ScanLine },
    { title: 'Cambia el responsable', description: 'Desde la ficha del activo, botón "Cambiar responsable", cuando el equipo cambie de dueño.', icon: UserCog },
    { title: 'Presta o recibe un equipo', description: 'Desde Préstamos o la ficha del activo: registra la salida y, al regresar, la devolución.', icon: UsersRound },
    { title: 'Registra una revisión', description: 'Deja constancia de que el equipo fue verificado físicamente (ubicación y responsable correctos).', icon: ClipboardCheck },
    { title: 'Da de baja un activo', description: 'Cuando el equipo deja de estar en uso: motivo, fecha y observaciones quedan en su historial.', icon: Ban },
    { title: 'Crea una auditoría', description: 'Desde Auditorías: levanta físicamente el inventario de una sucursal escaneando cada QR.', icon: ClipboardCheck },
    { title: 'Genera reportes', description: 'Desde Reportes: exporta a PDF o Excel el inventario, bajas, préstamos, piezas o auditorías.', icon: FileBarChart },
];

const concepts = [
    {
        icon: Landmark,
        title: 'Empresa vs. sucursal',
        body: 'La empresa es el dueño legal de los activos (una razón social). La sucursal es una ubicación física de esa empresa (una oficina, bodega o planta). Una empresa puede tener varias sucursales.',
    },
    {
        icon: Users,
        title: 'Responsable vs. usuario',
        body: 'El "responsable" es la persona física a la que se le asigna un activo (puede no tener acceso al sistema). El "usuario" es una cuenta con la que alguien inicia sesión en el sistema. No siempre son la misma persona.',
    },
    {
        icon: Boxes,
        title: 'Activo vs. pieza/refacción',
        body: 'Un "activo" es un equipo completo dado de alta de forma independiente (una laptop, un monitor). Una "pieza" es un componente o refacción (RAM, SSD, cargador) que puede existir suelta o formar parte de un activo.',
    },
    {
        icon: Layers,
        title: 'Pieza ensamblada',
        body: 'Cuando una pieza está "ensamblada" y vinculada a un activo (campo related_asset_id), significa que forma parte física de ese equipo. Por ejemplo, una laptop puede tener RAM y SSD ensambladas: se ven en la pestaña "Piezas" del activo.',
    },
    {
        icon: QrCode,
        title: 'QR permanente',
        body: 'El código QR de un activo o pieza no cambia aunque edites su clave, marca, responsable o ubicación: apunta siempre al mismo registro por un identificador interno fijo.',
    },
    {
        icon: Tags,
        title: 'Selección masiva de etiquetas',
        body: 'En Activos o en Etiquetas QR puedes marcar varios equipos a la vez ("Generar etiquetas QR" / checkboxes) y generar un solo PDF con todas sus etiquetas, eligiendo tamaño y columnas.',
    },
    {
        icon: SlidersHorizontal,
        title: 'Filtros',
        body: 'Casi todos los listados permiten combinar varios filtros (empresa, sucursal, tipo, estatus, fechas...). El botón "Limpiar filtros" aparece cuando hay al menos uno activo y los quita todos de un clic.',
    },
    {
        icon: FileBarChart,
        title: 'Reportes',
        body: 'La sección Reportes exporta exactamente lo que ves filtrado en pantalla: PDF con KPIs, gráficas y tabla; Excel con una hoja "Datos" y otra "Resumen" con esos mismos indicadores.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Movimientos / historial',
        body: 'Cada cambio relevante de un activo (responsable, ubicación, estatus, baja) queda registrado automáticamente en su pestaña "Historial", con fecha y usuario que lo hizo.',
    },
    {
        icon: ClipboardCheck,
        title: 'Auditorías',
        body: 'Una auditoría es un levantamiento físico: eliges una sucursal, se genera la lista de activos esperados, y al escanear cada QR se marca como encontrado o se reporta una diferencia (ubicación incorrecta, dañado, etc.).',
    },
];

const tips: { module: string; icon: typeof Boxes; text: string }[] = [
    { module: 'Activos', icon: Boxes, text: 'La vista de tarjetas muestra la foto más reciente; cambia a vista de tabla para comparar muchos campos a la vez.' },
    { module: 'Piezas', icon: Package, text: '"Ensamblada" indica que la pieza forma parte física de un activo (ligada por related_asset_id); si no, existe suelta en almacén.' },
    { module: 'Préstamos', icon: UsersRound, text: 'Un préstamo vencido es aquel cuya fecha de devolución esperada ya pasó y sigue en estatus "Prestado".' },
    { module: 'Auditorías', icon: ClipboardCheck, text: 'Solo se puede escanear dentro de una auditoría "En progreso"; finalízala cuando termines el levantamiento físico.' },
    { module: 'Etiquetas QR', icon: Printer, text: 'Elige un tamaño más pequeño para accesorios (mouse, cargador) y uno grande para equipo voluminoso (laptop, monitor); la vista previa te muestra el resultado antes de imprimir.' },
    { module: 'Reportes', icon: FileBarChart, text: 'El panel de Inventario se actualiza en vivo con tus filtros; los otros reportes muestran sus KPIs abajo de la descripción antes de exportarlos.' },
];
</script>

<template>
    <Head title="Ayuda" />

    <div class="flex flex-col gap-6">
        <PageHeader title="Ayuda" description="Guía rápida del sistema: primeros pasos, conceptos clave y tips por módulo" />

        <Tabs default-value="inicio" class="w-full">
            <TabsList>
                <TabsTrigger value="inicio">Inicio rápido</TabsTrigger>
                <TabsTrigger value="conceptos">Conceptos clave</TabsTrigger>
                <TabsTrigger value="tips">Tips por módulo</TabsTrigger>
            </TabsList>

            <TabsContent value="inicio" class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    Sigue estos pasos en orden la primera vez que configures el sistema. Después, solo usarás los pasos 5 en
                    adelante para el día a día.
                </p>
                <ol class="space-y-0">
                    <li v-for="(step, index) in steps" :key="step.title" class="relative border-l border-border py-3 pl-10 last:border-transparent">
                        <span
                            class="absolute top-3 -left-4 flex size-8 items-center justify-center rounded-full border border-border bg-card text-xs font-semibold text-foreground"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="flex items-start gap-2">
                            <component :is="step.icon" class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ step.title }}</p>
                                <p class="text-sm text-muted-foreground">{{ step.description }}</p>
                            </div>
                        </div>
                    </li>
                </ol>
            </TabsContent>

            <TabsContent value="conceptos" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Card v-for="concept in concepts" :key="concept.title">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <component :is="concept.icon" class="size-4 text-primary" />
                                {{ concept.title }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">{{ concept.body }}</p>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <TabsContent value="tips" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div v-for="tip in tips" :key="tip.module" class="flex gap-3 rounded-xl border border-border bg-card p-4">
                        <component :is="tip.icon" class="mt-0.5 size-4 shrink-0 text-primary" />
                        <div>
                            <p class="text-sm font-medium text-foreground">{{ tip.module }}</p>
                            <p class="text-sm text-muted-foreground">{{ tip.text }}</p>
                        </div>
                    </div>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
