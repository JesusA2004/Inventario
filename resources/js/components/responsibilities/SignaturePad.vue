<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const emit = defineEmits<{ 'update:hasSignature': [boolean] }>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
let drawing = false;
let hasStroke = false;
let lastX = 0;
let lastY = 0;

function getPos(event: PointerEvent) {
    const rect = canvasRef.value!.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}

function start(event: PointerEvent) {
    if (!ctx) {
        return;
    }

    drawing = true;
    const { x, y } = getPos(event);
    lastX = x;
    lastY = y;
    (event.target as Element).setPointerCapture?.(event.pointerId);
}

function move(event: PointerEvent) {
    if (!drawing || !ctx) {
        return;
    }

    const { x, y } = getPos(event);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastX = x;
    lastY = y;

    if (!hasStroke) {
        hasStroke = true;
        emit('update:hasSignature', true);
    }
}

function end() {
    drawing = false;
}

function resizeCanvas() {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ratio = window.devicePixelRatio || 1;
    const { width, height } = canvas.getBoundingClientRect();
    canvas.width = width * ratio;
    canvas.height = height * ratio;

    ctx = canvas.getContext('2d');

    if (ctx) {
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f2a4a';
    }
}

function onResize() {
    // Redimensionar borra el trazo (el canvas se reinicia): es una
    // limitación aceptable frente a un cambio de orientación a mitad de la
    // firma, que en la práctica es poco frecuente.
    resizeCanvas();
    hasStroke = false;
    emit('update:hasSignature', false);
}

onMounted(() => {
    resizeCanvas();
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize);
});

function clear() {
    const canvas = canvasRef.value;

    if (!canvas || !ctx) {
        return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasStroke = false;
    emit('update:hasSignature', false);
}

function toDataUrl(): string | null {
    if (!hasStroke || !canvasRef.value) {
        return null;
    }

    return canvasRef.value.toDataURL('image/png');
}

defineExpose({ clear, toDataUrl });
</script>

<template>
    <canvas
        ref="canvasRef"
        class="h-44 w-full touch-none rounded-lg border-2 border-dashed border-slate-300 bg-white"
        @pointerdown="start"
        @pointermove="move"
        @pointerup="end"
        @pointerleave="end"
        @pointercancel="end"
    />
</template>
