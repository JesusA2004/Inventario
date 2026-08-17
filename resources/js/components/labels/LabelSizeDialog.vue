<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fitsColumns, maxWidthForColumns, resolveDimensions } from '@/lib/labelSizes';
import type { LabelColumns, LabelSizeKey, LabelSizesConfig } from '@/lib/labelSizes';

type PreviewAsset = {
    type_name: string;
    internal_code: string;
    serial_number: string | null;
    company_name: string;
    qr_image_url: string | null;
};

const props = withDefaults(
    defineProps<{
        config: LabelSizesConfig;
        count: number;
        previewAsset: PreviewAsset | null;
        /** Oculta el selector de columnas cuando se imprime una sola etiqueta suelta (no aplica a una hoja en lote). */
        showColumns?: boolean;
    }>(),
    { showColumns: true },
);

const emit = defineEmits<{
    confirm: [payload: { size: LabelSizeKey; columns: LabelColumns; widthMm: number; heightMm: number }];
}>();

const open = defineModel<boolean>('open', { default: false });

const size = ref<LabelSizeKey>('medium');
const columns = ref<LabelColumns>(2);
const customWidth = ref(50);
const customHeight = ref(40);

const sizeOptions: { key: LabelSizeKey; label: string }[] = [
    { key: 'small', label: 'Pequeño' },
    { key: 'medium', label: 'Mediano' },
    { key: 'large', label: 'Grande' },
    { key: 'custom', label: 'Personalizado' },
];

const dimensions = computed(() => resolveDimensions(props.config, size.value, customWidth.value, customHeight.value));

function widthForSize(candidate: LabelSizeKey): number {
    return candidate === 'custom' ? customWidth.value : props.config.presets[candidate].width;
}

function isCompatible(candidate: LabelSizeKey, cols: LabelColumns): boolean {
    // Una etiqueta suelta tiene su propia página del tamaño exacto: el
    // límite de ancho por columnas de la hoja en lote no aplica aquí.
    if (!props.showColumns) {
        return true;
    }

    return fitsColumns(props.config, widthForSize(candidate), cols);
}

const columnOptions: { value: LabelColumns; label: string }[] = [
    { value: 2, label: '2 columnas' },
    { value: 3, label: '3 columnas' },
];

// Si el usuario elige una combinación que ya no cabe (p. ej. Grande + 3
// columnas), se cambia automáticamente a la opción compatible más cercana en
// vez de dejar que genere una etiqueta cortada.
watch([size, columns, customWidth], () => {
    if (!isCompatible(size.value, columns.value)) {
        const fallback = columnOptions.find((option) => isCompatible(size.value, option.value));

        if (fallback) {
            columns.value = fallback.value;
        }
    }
});

const incompatibleReason = computed(() => {
    if (isCompatible(size.value, columns.value)) {
        return null;
    }

    return `Este tamaño no cabe en ${columns.value} columnas sin cortarse (máximo ${maxWidthForColumns(props.config, columns.value)}mm de ancho). Se ajustará automáticamente.`;
});

const previewScale = computed(() => {
    // Escala visual fija para que el diálogo quepa cómodo sin importar el
    // tamaño real de la etiqueta (px por mm).
    const maxPreviewWidthPx = 220;

    return Math.min(3.4, maxPreviewWidthPx / dimensions.value.width);
});

function confirm() {
    emit('confirm', {
        size: size.value,
        columns: columns.value,
        widthMm: dimensions.value.width,
        heightMm: dimensions.value.height,
    });
    open.value = false;
}
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Tamaño de etiqueta QR</DialogTitle>
                <DialogDescription>
                    Elige el tamaño según el equipo: pequeño para accesorios (mouse, cargador), grande para equipos
                    grandes (laptop, monitor).
                    <template v-if="showColumns"> {{ count }} activo(s) seleccionados.</template>
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label>Tamaño</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="option in sizeOptions"
                                :key="option.key"
                                type="button"
                                class="rounded-lg border p-2 text-left text-sm transition-colors"
                                :class="
                                    size === option.key
                                        ? 'border-primary bg-accent/60 text-foreground'
                                        : 'border-border text-muted-foreground hover:border-primary/50'
                                "
                                @click="size = option.key"
                            >
                                <span class="block font-medium">{{ option.label }}</span>
                                <span v-if="option.key !== 'custom'" class="block text-xs text-muted-foreground">
                                    {{ config.presets[option.key].hint }}
                                </span>
                                <span v-else class="block text-xs text-muted-foreground">Ancho/alto a tu medida</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="size === 'custom'" class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <Label for="label-width">Ancho (mm)</Label>
                            <Input
                                id="label-width"
                                v-model.number="customWidth"
                                type="number"
                                :min="config.bounds.minWidth"
                                :max="config.bounds.maxWidth"
                                step="1"
                            />
                            <p class="text-xs text-muted-foreground">{{ config.bounds.minWidth }}–{{ config.bounds.maxWidth }}mm</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="label-height">Alto (mm)</Label>
                            <Input
                                id="label-height"
                                v-model.number="customHeight"
                                type="number"
                                :min="config.bounds.minHeight"
                                :max="config.bounds.maxHeight"
                                step="1"
                            />
                            <p class="text-xs text-muted-foreground">{{ config.bounds.minHeight }}–{{ config.bounds.maxHeight }}mm</p>
                        </div>
                    </div>

                    <div v-if="showColumns" class="grid gap-2">
                        <Label>Columnas por hoja</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="option in columnOptions"
                                :key="option.value"
                                type="button"
                                class="rounded-lg border p-2 text-sm transition-colors"
                                :class="[
                                    columns === option.value
                                        ? 'border-primary bg-accent/60 text-foreground'
                                        : 'border-border text-muted-foreground hover:border-primary/50',
                                    !isCompatible(size, option.value) && 'cursor-not-allowed opacity-40',
                                ]"
                                :disabled="!isCompatible(size, option.value)"
                                @click="columns = option.value"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                        <p v-if="incompatibleReason" class="text-xs text-amber-600 dark:text-amber-500">
                            {{ incompatibleReason }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 p-4">
                    <p class="text-xs font-medium text-muted-foreground uppercase">Vista previa aproximada</p>
                    <div
                        class="flex flex-col items-center justify-center gap-1 rounded border border-border bg-card text-center shadow-sm"
                        :style="{
                            width: `${dimensions.width * previewScale}px`,
                            minHeight: `${dimensions.height * previewScale}px`,
                            padding: `${3 * previewScale}px`,
                        }"
                    >
                        <span
                            class="font-bold tracking-wide text-foreground uppercase"
                            :style="{ fontSize: `${Math.max(7, dimensions.width * 0.19 * previewScale * 0.6)}px` }"
                        >
                            {{ previewAsset?.type_name ?? 'TIPO DE EQUIPO' }}
                        </span>
                        <span
                            class="font-bold text-foreground"
                            :style="{ fontSize: `${Math.max(9, dimensions.width * 0.27 * previewScale * 0.6)}px` }"
                        >
                            {{ previewAsset?.internal_code ?? 'CML-EC-000' }}
                        </span>
                        <img
                            v-if="previewAsset?.qr_image_url"
                            :src="previewAsset.qr_image_url"
                            alt="QR"
                            class="shrink-0"
                            :style="{ width: `${dimensions.qr * previewScale}px`, height: `${dimensions.qr * previewScale}px` }"
                        />
                        <div
                            v-else
                            class="grid shrink-0 grid-cols-4 grid-rows-4 gap-px bg-foreground/70 p-1"
                            :style="{ width: `${dimensions.qr * previewScale}px`, height: `${dimensions.qr * previewScale}px` }"
                        >
                            <span v-for="n in 16" :key="n" :class="[n % 3 === 0 ? 'bg-transparent' : 'bg-background']" />
                        </div>
                        <span
                            v-if="previewAsset?.serial_number"
                            class="text-muted-foreground"
                            :style="{ fontSize: `${Math.max(6, dimensions.width * 0.15 * previewScale * 0.6)}px` }"
                        >
                            S/N: {{ previewAsset.serial_number }}
                        </span>
                        <span
                            class="font-semibold text-primary"
                            :style="{ fontSize: `${Math.max(6, dimensions.width * 0.15 * previewScale * 0.6)}px` }"
                        >
                            {{ previewAsset?.company_name ?? 'Empresa' }}
                        </span>
                    </div>
                    <p class="text-center text-xs text-muted-foreground">
                        {{ dimensions.width }}mm × {{ dimensions.height }}mm · QR {{ dimensions.qr }}mm
                        <template v-if="showColumns">· {{ columns }} por fila</template>
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="open = false">Cancelar</Button>
                <Button type="button" @click="confirm">{{ showColumns ? `Generar PDF (${count})` : 'Imprimir etiqueta' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
