<script setup lang="ts">
import type { CalendarDate } from '@internationalized/date';
import { getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        placeholder?: string;
        disabled?: boolean;
        class?: string;
        fromToday?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: 'Selecciona una fecha',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const calendarValue = computed({
    get: () => (props.modelValue ? parseDate(props.modelValue) : undefined),
    set: (value: CalendarDate | undefined) => {
        emit('update:modelValue', value ? value.toString() : null);
    },
});

const formatted = computed(() => {
    if (!props.modelValue) {
        return null;
    }

    const date = parseDate(props.modelValue).toDate(getLocalTimeZone());

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
});

const maxValue = computed(() =>
    props.fromToday ? undefined : today(getLocalTimeZone()),
);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                :disabled="disabled"
                :class="
                    cn(
                        'w-full justify-start text-left font-normal',
                        !modelValue && 'text-muted-foreground',
                        props.class,
                    )
                "
            >
                <CalendarIcon class="mr-2 size-4 shrink-0" />
                <span class="truncate">{{ formatted ?? placeholder }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                v-model="calendarValue"
                locale="es"
                :max-value="fromToday ? undefined : maxValue"
                initial-focus
            />
        </PopoverContent>
    </Popover>
</template>
