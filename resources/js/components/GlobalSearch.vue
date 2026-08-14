<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Boxes, Search, User } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { getJson } from '@/lib/http';

type AssetResult = { public_id: string; internal_code: string; name: string; subtitle: string };
type ResponsibleResult = { id: number; full_name: string };

const open = ref(false);
const query = ref('');
const assets = ref<AssetResult[]>([]);
const responsiblePeople = ref<ResponsibleResult[]>([]);
let debounceTimer: ReturnType<typeof setTimeout>;

function onKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

watch(query, (value) => {
    clearTimeout(debounceTimer);

    if (!value || value.length < 2) {
        assets.value = [];
        responsiblePeople.value = [];

        return;
    }

    debounceTimer = setTimeout(async () => {
        const result = await getJson<{ assets: AssetResult[]; responsiblePeople: ResponsibleResult[] }>('/buscar', { q: value });
        assets.value = result.assets;
        responsiblePeople.value = result.responsiblePeople;
    }, 250);
});

function goToAsset(publicId: string) {
    open.value = false;
    router.visit(`/activos/${publicId}`);
}

function goToResponsible(id: number) {
    open.value = false;
    router.visit(`/activos?responsible_id=${id}`);
}
</script>

<template>
    <button
        type="button"
        class="flex items-center gap-2 rounded-md border border-border bg-background px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent"
        @click="open = true"
    >
        <Search class="size-4" />
        <span class="hidden sm:inline">Buscar activo...</span>
        <kbd class="ml-4 hidden rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium sm:inline">Ctrl K</kbd>
    </button>

    <CommandDialog v-model:open="open" title="Búsqueda global" description="Busca activos o responsables">
        <CommandInput v-model="query" placeholder="Buscar por clave, dispositivo, serie, marca o responsable..." />
        <CommandList>
            <CommandEmpty v-if="query.length >= 2">Sin resultados.</CommandEmpty>
            <CommandGroup v-if="assets.length > 0" heading="Activos">
                <CommandItem v-for="asset in assets" :key="asset.public_id" :value="asset.internal_code" @select="() => goToAsset(asset.public_id)">
                    <Boxes class="mr-2 size-4 text-muted-foreground" />
                    <div class="flex flex-col">
                        <span class="font-mono text-sm">{{ asset.internal_code }}</span>
                        <span class="text-xs text-muted-foreground">{{ asset.name }} · {{ asset.subtitle }}</span>
                    </div>
                </CommandItem>
            </CommandGroup>
            <CommandGroup v-if="responsiblePeople.length > 0" heading="Responsables">
                <CommandItem
                    v-for="person in responsiblePeople"
                    :key="person.id"
                    :value="`responsable-${person.full_name}`"
                    @select="() => goToResponsible(person.id)"
                >
                    <User class="mr-2 size-4 text-muted-foreground" />
                    {{ person.full_name }}
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
