export type LabelSizeKey = 'small' | 'medium' | 'large' | 'custom';

export type LabelSizePreset = {
    label: string;
    hint: string;
    width: number;
    height: number;
    qr: number;
    font_type: number;
    font_code: number;
    font_small: number;
};

export type LabelSizesConfig = {
    presets: Record<'small' | 'medium' | 'large', LabelSizePreset>;
    bounds: { minWidth: number; maxWidth: number; minHeight: number; maxHeight: number };
    maxWidthByColumns: Record<string, number>;
};

export type LabelColumns = 2 | 3;

/** Ancho/alto/QR efectivos (mm) para la combinación tamaño+columnas elegida. */
export function resolveDimensions(
    config: LabelSizesConfig,
    size: LabelSizeKey,
    customWidth: number,
    customHeight: number,
): { width: number; height: number; qr: number } {
    if (size === 'custom') {
        const width = clamp(customWidth, config.bounds.minWidth, config.bounds.maxWidth);
        const height = clamp(customHeight, config.bounds.minHeight, config.bounds.maxHeight);
        const qr = Math.max(14, Math.min(Math.min(width, height) * 0.55, Math.min(width, height) - 10));

        return { width, height, qr: Math.round(qr * 10) / 10 };
    }

    const preset = config.presets[size];

    return { width: preset.width, height: preset.height, qr: preset.qr };
}

export function maxWidthForColumns(config: LabelSizesConfig, columns: LabelColumns): number {
    return config.maxWidthByColumns[String(columns)] ?? config.maxWidthByColumns['2'];
}

export function fitsColumns(config: LabelSizesConfig, width: number, columns: LabelColumns): boolean {
    return width <= maxWidthForColumns(config, columns);
}

function clamp(value: number, min: number, max: number): number {
    if (Number.isNaN(value)) {
        return min;
    }

    return Math.min(max, Math.max(min, value));
}
