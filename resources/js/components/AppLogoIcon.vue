<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M12 2.5 20.1 7 12 11.5 3.9 7 12 2.5Z M3.45 7.75 11.55 12.25 11.55 21.25 3.45 16.75 3.45 7.75Z M20.55 7.75 20.55 16.75 12.45 21.25 12.45 12.25 20.55 7.75Z"
        />
    </svg>
</template>
