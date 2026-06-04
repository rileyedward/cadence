<script setup lang="ts">
import { reactive, watch } from 'vue';
import IntentMultiSelect from '@/components/cadence/IntentMultiSelect.vue';
import type { DefaultIntent } from '@/components/cadence/IntentMultiSelect.vue';
import TimeRangeInput from '@/components/cadence/TimeRangeInput.vue';
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
import type { BlockTypeDef } from '@/pages/Templates/Edit.vue';
import { FlexibilityMode } from '@/types/enums';
import type { BlockDTO } from '@/types/models';

const props = defineProps<{
    block?: BlockDTO | null;
    blockTypes?: BlockTypeDef[];
    processing?: boolean;
    errors?: Record<string, string>;
}>();

export type BlockFormPayload = {
    name: string;
    start_time: string;
    end_time: string;
    flexibility_mode: string;
    category: string | null;
    priority: number;
    constraints: {
        minDuration?: number;
        maxDuration?: number;
        minRestAfter?: number;
    } | null;
    context: { location?: string; mobility?: string; social?: string } | null;
    default_intents: DefaultIntent[];
};

const emit = defineEmits<{ submit: [BlockFormPayload]; cancel: [] }>();

function initial(): BlockFormPayload {
    const b = props.block;

    return {
        name: b?.name ?? '',
        start_time: b?.start_time ?? '09:00',
        end_time: b?.end_time ?? '10:00',
        flexibility_mode: b?.flexibility_mode ?? 'adaptive',
        category: b?.category ?? null,
        priority: b?.priority ?? 0,
        constraints:
            (b?.constraints as BlockFormPayload['constraints']) ?? null,
        context: (b?.context as BlockFormPayload['context']) ?? null,
        default_intents:
            b?.default_intents?.map((d) => ({
                intent_id: d.id,
                weight: d.weight,
            })) ?? [],
    };
}

const form = reactive(initial());

watch(
    () => props.block,
    () => Object.assign(form, initial()),
);

// Selecting a plugin-provided block type applies its preset defaults (doc 14).
function applyBlockType(key: string) {
    const type = props.blockTypes?.find((t) => t.key === key);

    if (!type) {
return;
}

    if (type.defaults.flexibility_mode) {
form.flexibility_mode = type.defaults.flexibility_mode;
}

    if (type.defaults.constraints) {
        form.constraints = {
            ...(form.constraints ?? {}),
            ...(type.defaults.constraints as BlockFormPayload['constraints']),
        };
    }

    if (type.defaults.context) {
        form.context = {
            ...(form.context ?? {}),
            ...(type.defaults.context as BlockFormPayload['context']),
        };
    }
}

function submit() {
    emit('submit', { ...form });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="block-name">Name</Label>
            <Input
                id="block-name"
                v-model="form.name"
                required
                placeholder="e.g. Deep Work"
            />
            <p v-if="errors?.name" class="text-sm text-destructive">
                {{ errors.name }}
            </p>
        </div>

        <div v-if="blockTypes?.length" class="grid gap-2">
            <Label>Block type (applies presets)</Label>
            <Select @update:model-value="applyBlockType(String($event))">
                <SelectTrigger><SelectValue placeholder="Custom" /></SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="t in blockTypes" :key="t.key" :value="t.key">
                        {{ t.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Time window</Label>
            <TimeRangeInput
                v-model:start="form.start_time"
                v-model:end="form.end_time"
            />
            <p v-if="errors?.end_time" class="text-sm text-destructive">
                {{ errors.end_time }}
            </p>
        </div>

        <div class="grid gap-2">
            <Label>Flexibility</Label>
            <Select v-model="form.flexibility_mode">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="mode in FlexibilityMode"
                        :key="mode"
                        :value="mode"
                    >
                        {{ mode }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Default intents</Label>
            <IntentMultiSelect v-model="form.default_intents" />
        </div>

        <details class="rounded-lg border border-border p-3 text-sm">
            <summary class="cursor-pointer font-medium">
                Constraints & context
            </summary>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <label class="grid gap-1">
                    <span class="text-muted-foreground"
                        >Min duration (min)</span
                    >
                    <Input
                        type="number"
                        min="0"
                        :model-value="form.constraints?.minDuration ?? ''"
                        @update:model-value="
                            form.constraints = {
                                ...(form.constraints ?? {}),
                                minDuration: Number($event) || undefined,
                            }
                        "
                    />
                </label>
                <label class="grid gap-1">
                    <span class="text-muted-foreground"
                        >Max duration (min)</span
                    >
                    <Input
                        type="number"
                        min="0"
                        :model-value="form.constraints?.maxDuration ?? ''"
                        @update:model-value="
                            form.constraints = {
                                ...(form.constraints ?? {}),
                                maxDuration: Number($event) || undefined,
                            }
                        "
                    />
                </label>
                <label class="col-span-2 grid gap-1">
                    <span class="text-muted-foreground">Location</span>
                    <Input
                        :model-value="form.context?.location ?? ''"
                        @update:model-value="
                            form.context = {
                                ...(form.context ?? {}),
                                location: String($event),
                            }
                        "
                    />
                </label>
            </div>
        </details>

        <div class="flex justify-end gap-2 pt-2">
            <Button type="button" variant="ghost" @click="emit('cancel')"
                >Cancel</Button
            >
            <Button type="submit" :disabled="processing">Save block</Button>
        </div>
    </form>
</template>
