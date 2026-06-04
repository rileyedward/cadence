<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TemplateScope } from '@/types/enums';
import type { TemplateAssignmentDTO } from '@/types/models';

const props = defineProps<{
    templateId: number;
    assignments: TemplateAssignmentDTO[];
}>();

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const form = useForm<{
    scope: string;
    days_of_week: number[];
    priority: number;
}>({ scope: 'weekday', days_of_week: [], priority: 0 });

function toggleDay(d: number) {
    form.days_of_week = form.days_of_week.includes(d)
        ? form.days_of_week.filter((x) => x !== d)
        : [...form.days_of_week, d];
}

function add() {
    form.post(`/templates/${props.templateId}/assignments`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function remove(a: TemplateAssignmentDTO) {
    router.delete(`/templates/${props.templateId}/assignments/${a.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <section>
        <h2 class="mb-3 text-lg font-semibold">
            When does this template apply?
        </h2>

        <div class="grid gap-2">
            <div
                v-for="a in assignments"
                :key="a.id"
                class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3 text-sm"
            >
                <span>
                    <span class="font-medium capitalize">{{ a.scope }}</span>
                    <span v-if="a.scope === 'custom' && a.days_of_week">
                        · {{ a.days_of_week.map((d) => DAYS[d]).join(', ') }}
                    </span>
                    <span v-if="a.starts_on"> · from {{ a.starts_on }}</span>
                    <span v-if="a.ends_on"> to {{ a.ends_on }}</span>
                    <span class="text-muted-foreground">
                        · priority {{ a.priority }}</span
                    >
                </span>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Remove"
                    @click="remove(a)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <form
            class="mt-3 space-y-3 rounded-lg border border-dashed border-border p-3"
            @submit.prevent="add"
        >
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label>Scope</Label>
                    <Select v-model="form.scope">
                        <SelectTrigger class="w-36"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="s in TemplateScope"
                                :key="s"
                                :value="s"
                                >{{ s }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-1">
                    <Label>Priority</Label>
                    <Input
                        v-model.number="form.priority"
                        type="number"
                        min="0"
                        class="w-24"
                    />
                </div>
            </div>

            <div v-if="form.scope === 'custom'" class="flex flex-wrap gap-1">
                <button
                    v-for="(label, d) in DAYS"
                    :key="d"
                    type="button"
                    class="min-h-[36px] rounded-md border px-3 text-sm"
                    :class="
                        form.days_of_week.includes(d)
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border'
                    "
                    @click="toggleDay(d)"
                >
                    {{ label }}
                </button>
            </div>

            <Button type="submit" size="sm" :disabled="form.processing"
                >Add rule</Button
            >
        </form>
    </section>
</template>
