<script setup lang="ts">
import { Check, ChevronsUpDown, Loader2, X } from '@lucide/vue';
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
        loading?: boolean;
        clearable?: boolean;
        class?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Selecciona una opción',
        searchPlaceholder: 'Buscar...',
        emptyText: 'Sin resultados.',
        disabled: false,
        loading: false,
        clearable: false,
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

function onClear(event: Event) {
    event.stopPropagation();
    emit('update:modelValue', null);
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
                :disabled="disabled || loading"
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
                <Loader2 v-if="loading" class="ml-2 size-4 shrink-0 animate-spin opacity-50" />
                <X
                    v-else-if="clearable && selected"
                    class="ml-2 size-4 shrink-0 opacity-50 hover:opacity-100"
                    @click="onClear"
                />
                <ChevronsUpDown v-else class="ml-2 size-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-(--reka-popover-trigger-width) min-w-56 p-0"
            align="start"
        >
            <Command>
                <CommandInput :placeholder="searchPlaceholder" />
                <CommandList class="max-h-64">
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
