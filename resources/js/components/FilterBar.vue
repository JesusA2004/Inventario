<script setup lang="ts">
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

const props = withDefaults(
    defineProps<{
        search?: string;
        searchPlaceholder?: string;
        activeFiltersCount?: number;
    }>(),
    {
        search: '',
        searchPlaceholder: 'Buscar...',
        activeFiltersCount: 0,
    },
);

const emit = defineEmits<{
    'update:search': [value: string];
    clear: [];
}>();

const sheetOpen = ref(false);

function clear() {
    emit('clear');
    sheetOpen.value = false;
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative flex-1 lg:max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    :model-value="search"
                    @update:model-value="
                        (value) => emit('update:search', String(value ?? ''))
                    "
                    :placeholder="searchPlaceholder"
                    class="pl-8"
                />
            </div>

            <div class="hidden items-center gap-2 lg:flex">
                <slot name="actions" />
                <Button
                    v-if="activeFiltersCount > 0 || search"
                    variant="ghost"
                    size="sm"
                    @click="clear"
                >
                    <X class="mr-1 size-4" />
                    Limpiar filtros
                </Button>
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                <Sheet v-model:open="sheetOpen">
                    <SheetTrigger as-child>
                        <Button variant="outline" class="flex-1 gap-2 sm:flex-none">
                            <SlidersHorizontal class="size-4" />
                            Filtros
                            <span
                                v-if="activeFiltersCount"
                                class="ml-1 flex size-5 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground"
                            >
                                {{ activeFiltersCount }}
                            </span>
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="bottom"
                        class="max-h-[85vh] overflow-y-auto"
                    >
                        <SheetHeader>
                            <SheetTitle>Filtros</SheetTitle>
                        </SheetHeader>
                        <div class="flex flex-col gap-4 px-4 pb-4">
                            <slot />
                        </div>
                        <div class="flex flex-col gap-2 border-t px-4 pt-4 pb-2">
                            <slot name="actions" />
                            <Button
                                v-if="props.activeFiltersCount > 0 || props.search"
                                variant="outline"
                                @click="clear"
                            >
                                <X class="mr-1 size-4" />
                                Limpiar filtros
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <div class="hidden flex-wrap items-center gap-2 lg:flex">
            <slot />
        </div>
    </div>
</template>
