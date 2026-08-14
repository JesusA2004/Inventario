<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export type ComboboxOption = {
    value: string | number;
    label: string;
    description?: string;
};

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        options: ComboboxOption[];
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Selecciona una opción',
        searchPlaceholder: 'Buscar...',
        emptyText: 'Sin resultados.',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const open = ref(false);

const selected = computed(
    () =>
        props.options.find((option) => option.value === props.modelValue) ??
        null,
);

function onSelect(value: string | number) {
    emit('update:modelValue', props.modelValue === value ? null : value);
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :disabled="disabled"
                :class="
                    cn(
                        'w-full justify-between font-normal',
                        !selected && 'text-muted-foreground',
                        props.class,
                    )
                "
            >
                <span class="truncate">{{
                    selected ? selected.label : placeholder
                }}</span>
                <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-(--reka-popover-trigger-width) min-w-56 p-0"
            align="start"
        >
            <Command>
                <CommandInput :placeholder="searchPlaceholder" />
                <CommandList>
                    <CommandEmpty>{{ emptyText }}</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option.value"
                            :value="option.label"
                            @select="() => onSelect(option.value)"
                        >
                            <Check
                                :class="
                                    cn(
                                        'mr-2 size-4',
                                        modelValue === option.value
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )
                                "
                            />
                            <div class="flex flex-col">
                                <span>{{ option.label }}</span>
                                <span
                                    v-if="option.description"
                                    class="text-xs text-muted-foreground"
                                    >{{ option.description }}</span
                                >
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
                <div v-if="$slots.action" class="border-t p-1">
                    <slot name="action" :close="() => (open = false)" />
                </div>
            </Command>
        </PopoverContent>
    </Popover>
</template>
