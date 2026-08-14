<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    Ban,
    ClipboardCheck,
    Download,
    Edit,
    MapPinned,
    MoreHorizontal,
    Printer,
    RotateCcw,
    UserCog,
    UsersRound,
} from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import ChangeLocationDialog from '@/components/assets/ChangeLocationDialog.vue';
import ChangeResponsibleDialog from '@/components/assets/ChangeResponsibleDialog.vue';
import DecommissionDialog from '@/components/assets/DecommissionDialog.vue';
import RegisterReviewDialog from '@/components/assets/RegisterReviewDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type Person = { id: number; full_name: string } | null;

type AssetDetail = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    model: string | null;
    serial_number: string | null;
    status: { value: string; label: string; color: string } | null;
    in_inventory: boolean;
    company: { id: number; name: string; code: string } | null;
    branch: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    brand: { id: number; name: string } | null;
    assetType: { id: number; name: string } | null;
    currentResponsible: Person;
    deliveredByResponsible: Person;
    creator: { id: number; name: string } | null;
    components: string | null;
    specifications: string | null;
    notes: string | null;
    invoice_url: string | null;
    purchase_date: string | null;
    acquired_at: string | null;
    last_reviewed_at: string | null;
    decommissioned_at: string | null;
    decommission_reason: string | null;
    decommission_notes: string | null;
    files: {
        id: number;
        type: string;
        type_label: string;
        path: string;
        original_name: string;
        uploader: string | null;
        created_at: string;
    }[];
    movements: {
        id: number;
        type_label: string;
        field: string | null;
        old_value: string | null;
        new_value: string | null;
        comment: string | null;
        user: string | null;
        created_at: string;
    }[];
    reviews: {
        id: number;
        reviewed_at: string;
        physical_status: string;
        location_ok: boolean;
        responsible_ok: boolean;
        notes: string | null;
        user: string | null;
    }[];
    loans: {
        id: number;
        status: { value: string; label: string; color: string };
        loan_date: string;
        expected_return_date: string | null;
        actual_return_date: string | null;
        assigned_to: string | null;
        reason: string | null;
    }[];
    parts: {
        id: number;
        public_id: string;
        name: string;
        internal_code: string;
    }[];
};

const props = defineProps<{
    asset: AssetDetail;
    qrUrl: string;
    justCreated: boolean;
    decommissionReasons: { value: string; label: string }[];
    actionOptions: {
        branches: { id: number; name: string }[];
        departments: { id: number; name: string }[];
        responsiblePeople: { id: number; full_name: string }[];
    };
}>();

const responsibleDialogOpen = ref(false);
const locationDialogOpen = ref(false);
const reviewDialogOpen = ref(false);
const decommissionDialogOpen = ref(false);
const reactivateConfirmOpen = ref(false);
const reactivating = ref(false);

function reactivate() {
    reactivating.value = true;
    router.post(
        `/activos/${props.asset.public_id}/reactivar`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                reactivating.value = false;
                reactivateConfirmOpen.value = false;
            },
        },
    );
}

setLayoutProps({
    breadcrumbs: [
        { title: 'Activos', href: '/activos' },
        {
            title: props.asset.internal_code,
            href: `/activos/${props.asset.public_id}`,
        },
    ],
});

onMounted(() => {
    if (props.justCreated) {
        toast.success('Activo registrado correctamente.');
    }
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
</script>

<template>
    <Head :title="`${asset.internal_code} · ${asset.name}`" />

    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h1
                        class="text-2xl font-semibold tracking-tight text-foreground"
                    >
                        {{ asset.name }}
                    </h1>
                    <StatusBadge
                        v-if="asset.status"
                        :label="asset.status.label"
                        :color="asset.status.color"
                    />
                    <Badge v-if="!asset.in_inventory" variant="secondary"
                        >Fuera de inventario</Badge
                    >
                </div>
                <p class="font-mono text-sm text-muted-foreground">
                    {{ asset.internal_code }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ asset.company?.name }} · {{ asset.branch?.name }}
                    <span v-if="asset.department">
                        · {{ asset.department.name }}</span
                    >
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Link :href="`/activos/${asset.public_id}/editar`">
                    <Button variant="outline">
                        <Edit class="mr-1 size-4" />
                        Editar
                    </Button>
                </Link>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon">
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                            <Link :href="`/activos/${asset.public_id}/editar`">Editar</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="`/activos/${asset.public_id}/etiqueta`" target="_blank">Imprimir etiqueta</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="`/activos/${asset.public_id}/qr/descargar`">Descargar QR</a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="responsibleDialogOpen = true">
                            <UserCog class="mr-2 size-4" />
                            Cambiar responsable
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="locationDialogOpen = true">
                            <MapPinned class="mr-2 size-4" />
                            Cambiar ubicación
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="reviewDialogOpen = true">
                            <ClipboardCheck class="mr-2 size-4" />
                            Registrar revisión
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="asset.in_inventory" as-child>
                            <Link :href="`/prestamos/crear?asset_id=${asset.public_id}`">
                                <UsersRound class="mr-2 size-4" />
                                Prestar
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-if="asset.in_inventory"
                            class="text-destructive focus:text-destructive"
                            @click="decommissionDialogOpen = true"
                        >
                            <Ban class="mr-2 size-4" />
                            Dar de baja
                        </DropdownMenuItem>
                        <DropdownMenuItem v-else @click="reactivateConfirmOpen = true">
                            <RotateCcw class="mr-2 size-4" />
                            Reactivar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <Tabs default-value="general" class="w-full">
            <TabsList class="w-full justify-start overflow-x-auto">
                <TabsTrigger value="general">General</TabsTrigger>
                <TabsTrigger value="asignacion">Asignación</TabsTrigger>
                <TabsTrigger value="historial">Historial</TabsTrigger>
                <TabsTrigger value="prestamos">Préstamos</TabsTrigger>
                <TabsTrigger value="revisiones">Revisiones</TabsTrigger>
                <TabsTrigger value="archivos">Archivos</TabsTrigger>
                <TabsTrigger value="qr">QR</TabsTrigger>
            </TabsList>

            <TabsContent value="general" class="space-y-4">
                <div
                    class="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <p class="text-xs text-muted-foreground">Tipo</p>
                        <p class="text-sm font-medium">
                            {{ asset.assetType?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Marca</p>
                        <p class="text-sm font-medium">
                            {{ asset.brand?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Modelo</p>
                        <p class="text-sm font-medium">
                            {{ asset.model ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">N° de serie</p>
                        <p class="text-sm font-medium">
                            {{ asset.serial_number ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Fecha de alta
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(asset.acquired_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Fecha de compra
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(asset.purchase_date) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Última revisión
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(asset.last_reviewed_at) }}
                        </p>
                    </div>
                    <div v-if="asset.decommissioned_at">
                        <p class="text-xs text-muted-foreground">
                            Fecha de baja
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(asset.decommissioned_at) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="
                        asset.components || asset.specifications || asset.notes
                    "
                    class="grid gap-4 rounded-xl border border-border bg-card p-4"
                >
                    <div v-if="asset.components">
                        <p class="text-xs text-muted-foreground">
                            Componentes / incluye
                        </p>
                        <p class="text-sm whitespace-pre-line">
                            {{ asset.components }}
                        </p>
                    </div>
                    <Separator
                        v-if="asset.components && asset.specifications"
                    />
                    <div v-if="asset.specifications">
                        <p class="text-xs text-muted-foreground">
                            Especificaciones
                        </p>
                        <p class="text-sm whitespace-pre-line">
                            {{ asset.specifications }}
                        </p>
                    </div>
                    <Separator v-if="asset.specifications && asset.notes" />
                    <div v-if="asset.notes">
                        <p class="text-xs text-muted-foreground">
                            Observaciones
                        </p>
                        <p class="text-sm whitespace-pre-line">
                            {{ asset.notes }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="asset.invoice_url"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <p class="text-xs text-muted-foreground">Factura</p>
                    <a
                        :href="asset.invoice_url"
                        target="_blank"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        Ver documento de factura
                    </a>
                </div>
            </TabsContent>

            <TabsContent value="asignacion" class="space-y-4">
                <div class="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Responsable actual
                        </p>
                        <p class="text-sm font-medium">
                            {{
                                asset.currentResponsible?.full_name ??
                                'Sin asignar'
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Entregó / responsable de revisión
                        </p>
                        <p class="text-sm font-medium">
                            {{ asset.deliveredByResponsible?.full_name ?? '—' }}
                        </p>
                    </div>
                </div>
                <Button variant="outline" @click="responsibleDialogOpen = true">
                    <UserCog class="mr-1 size-4" />
                    Cambiar responsable
                </Button>
            </TabsContent>

            <TabsContent value="historial">
                <div
                    v-if="asset.movements.length === 0"
                    class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
                >
                    Todavía no hay movimientos registrados para este activo.
                </div>
                <ol v-else class="space-y-0">
                    <li
                        v-for="movement in asset.movements"
                        :key="movement.id"
                        class="relative border-l border-border py-3 pl-6 last:border-transparent"
                    >
                        <span
                            class="absolute top-4 -left-[5px] size-2.5 rounded-full bg-primary"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(movement.created_at) }}
                        </p>
                        <p class="text-sm font-medium text-foreground">
                            {{ movement.type_label }}
                        </p>
                        <p
                            v-if="movement.old_value || movement.new_value"
                            class="text-sm text-muted-foreground"
                        >
                            {{ movement.old_value ?? '—' }} →
                            {{ movement.new_value ?? '—' }}
                        </p>
                        <p
                            v-if="movement.comment"
                            class="text-sm text-muted-foreground"
                        >
                            {{ movement.comment }}
                        </p>
                        <p
                            v-if="movement.user"
                            class="text-xs text-muted-foreground"
                        >
                            Realizado por {{ movement.user }}
                        </p>
                    </li>
                </ol>
            </TabsContent>

            <TabsContent value="prestamos" class="space-y-4">
                <Link v-if="asset.in_inventory" :href="`/prestamos/crear?asset_id=${asset.public_id}`">
                    <Button variant="outline">
                        <UsersRound class="mr-1 size-4" />
                        Prestar
                    </Button>
                </Link>
                <div
                    v-if="asset.loans.length === 0"
                    class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
                >
                    Este activo no tiene préstamos registrados.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="loan in asset.loans"
                        :key="loan.id"
                        class="rounded-xl border border-border bg-card p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">
                                {{ loan.assigned_to ?? 'Sin asignar' }}
                            </p>
                            <StatusBadge
                                :label="loan.status.label"
                                :color="loan.status.color"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Prestado {{ formatDate(loan.loan_date) }}
                            <span v-if="loan.expected_return_date">
                                · Devolución esperada
                                {{
                                    formatDate(loan.expected_return_date)
                                }}</span
                            >
                        </p>
                        <p
                            v-if="loan.reason"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ loan.reason }}
                        </p>
                    </div>
                </div>
            </TabsContent>

            <TabsContent value="revisiones" class="space-y-4">
                <Button variant="outline" @click="reviewDialogOpen = true">
                    <ClipboardCheck class="mr-1 size-4" />
                    Registrar revisión
                </Button>
                <div
                    v-if="asset.reviews.length === 0"
                    class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
                >
                    Todavía no se ha registrado ninguna revisión.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="review in asset.reviews"
                        :key="review.id"
                        class="rounded-xl border border-border bg-card p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">
                                {{ formatDate(review.reviewed_at) }}
                            </p>
                            <Badge variant="outline">{{
                                review.physical_status
                            }}</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Ubicación
                            {{
                                review.location_ok ? 'correcta' : 'incorrecta'
                            }}
                            · Responsable
                            {{
                                review.responsible_ok
                                    ? 'correcto'
                                    : 'incorrecto'
                            }}
                        </p>
                        <p
                            v-if="review.notes"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ review.notes }}
                        </p>
                        <p
                            v-if="review.user"
                            class="text-xs text-muted-foreground"
                        >
                            Revisó {{ review.user }}
                        </p>
                    </div>
                </div>
            </TabsContent>

            <TabsContent value="archivos">
                <div
                    v-if="asset.files.length === 0"
                    class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
                >
                    No hay archivos adjuntos. Edita el activo para subir
                    facturas o fotografías.
                </div>
                <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <a
                        v-for="file in asset.files"
                        :key="file.id"
                        :href="`/storage/${file.path}`"
                        target="_blank"
                        class="rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-md"
                    >
                        <Badge variant="outline" class="mb-2">{{
                            file.type_label
                        }}</Badge>
                        <p class="truncate text-sm font-medium">
                            {{ file.original_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(file.created_at) }}
                        </p>
                    </a>
                </div>
            </TabsContent>

            <TabsContent value="qr">
                <div class="flex flex-col items-center gap-4 rounded-xl border border-border bg-card p-8 text-center">
                    <img :src="`/activos/${asset.public_id}/qr`" alt="Código QR" class="size-56 rounded-lg border border-border p-2" />
                    <div>
                        <p class="font-mono text-sm font-medium text-foreground">{{ asset.internal_code }}</p>
                        <p class="text-xs break-all text-muted-foreground">{{ qrUrl }}</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <a :href="`/activos/${asset.public_id}/qr/descargar`">
                            <Button variant="outline">
                                <Download class="mr-1 size-4" />
                                Descargar QR PNG
                            </Button>
                        </a>
                        <a :href="`/activos/${asset.public_id}/etiqueta`" target="_blank">
                            <Button>
                                <Printer class="mr-1 size-4" />
                                Imprimir etiqueta
                            </Button>
                        </a>
                    </div>
                    <p class="max-w-sm text-xs text-muted-foreground">
                        Este código es permanente: seguirá funcionando aunque cambies la clave, marca, sucursal o responsable del activo.
                    </p>
                </div>
            </TabsContent>
        </Tabs>
    </div>

    <ChangeResponsibleDialog
        v-model:open="responsibleDialogOpen"
        :asset-public-id="asset.public_id"
        :current-responsible-id="asset.currentResponsible?.id ?? null"
        :responsible-people="actionOptions.responsiblePeople"
    />
    <ChangeLocationDialog
        v-model:open="locationDialogOpen"
        :asset-public-id="asset.public_id"
        :branch-id="asset.branch?.id ?? 0"
        :department-id="asset.department?.id ?? null"
        :branches="actionOptions.branches"
        :departments="actionOptions.departments"
    />
    <RegisterReviewDialog v-model:open="reviewDialogOpen" :asset-public-id="asset.public_id" />
    <DecommissionDialog
        v-model:open="decommissionDialogOpen"
        :asset-public-id="asset.public_id"
        :reasons="decommissionReasons"
    />
    <ConfirmDialog
        :open="reactivateConfirmOpen"
        title="¿Reactivar este activo?"
        description="Volverá a contarse como parte del inventario activo."
        confirm-text="Reactivar"
        :loading="reactivating"
        @update:open="(value) => (reactivateConfirmOpen = value)"
        @confirm="reactivate"
    />
</template>
