<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Combobox from '@/components/Combobox.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Option = { id: number; name: string; company_id?: number | null };

const props = defineProps<{
    companies: Option[];
    branches: Option[];
    departments: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Auditorías', href: '/auditorias' },
            { title: 'Nueva auditoría', href: '/auditorias/crear' },
        ],
    },
});

const form = useForm({
    company_id: '' as number | string,
    branch_id: '' as number | string,
    department_id: '' as number | string,
    name: '',
});

const branchOptions = computed(() =>
    props.branches.filter((b) => String(b.company_id) === String(form.company_id)).map((b) => ({ value: b.id, label: b.name })),
);
const departmentOptions = computed(() =>
    props.departments
        .filter((d) => !d.company_id || String(d.company_id) === String(form.company_id))
        .map((d) => ({ value: d.id, label: d.name })),
);

watch(
    () => form.company_id,
    (newCompanyId) => {
        const branch = props.branches.find((b) => b.id === form.branch_id);

        if (branch && String(branch.company_id) !== String(newCompanyId)) {
            form.branch_id = '';
        }

        const department = props.departments.find((d) => d.id === form.department_id);

        if (department && department.company_id && String(department.company_id) !== String(newCompanyId)) {
            form.department_id = '';
        }
    },
);

// Encadenamiento inverso: elegir la sucursal autoselecciona su empresa.
watch(
    () => form.branch_id,
    (newBranchId) => {
        const branch = props.branches.find((b) => b.id === newBranchId);

        if (branch && branch.company_id != null && String(branch.company_id) !== String(form.company_id)) {
            form.company_id = branch.company_id;
        }
    },
);

function submit() {
    form.transform((data) => ({ ...data, department_id: data.department_id || null })).post('/auditorias');
}
</script>

<template>
    <Head title="Nueva auditoría" />

    <div class="mx-auto flex max-w-lg flex-col gap-6">
        <PageHeader title="Nueva auditoría" description="Selecciona el alcance del levantamiento físico" />

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label>Empresa</Label>
                <Combobox
                    v-model="form.company_id"
                    :options="companies.map((c) => ({ value: c.id, label: c.name }))"
                    placeholder="Selecciona una empresa"
                />
            </div>
            <div class="grid gap-2">
                <Label>Sucursal</Label>
                <Combobox v-model="form.branch_id" :options="branchOptions" placeholder="Selecciona una sucursal" :disabled="!form.company_id" />
            </div>
            <div class="grid gap-2">
                <Label>Área (opcional)</Label>
                <Combobox v-model="form.department_id" :options="departmentOptions" placeholder="Toda la sucursal" :disabled="!form.company_id" />
            </div>
            <div class="grid gap-2">
                <Label for="audit-name">Nombre (opcional)</Label>
                <Input id="audit-name" v-model="form.name" placeholder="Se genera automáticamente si se deja vacío" />
            </div>

            <Button type="submit" :disabled="form.processing || !form.company_id || !form.branch_id">
                <Spinner v-if="form.processing" class="mr-1" />
                Iniciar auditoría
            </Button>
        </form>
    </div>
</template>
