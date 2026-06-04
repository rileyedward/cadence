<script setup lang="ts">
import { GripVertical, Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDuration } from '@/lib/time';
import type { ActivityDTO } from '@/types/models';

export type PlanActivity = {
    activity_id: number;
    estimated_minutes: number;
    order: number;
};

const props = defineProps<{
    activityLibrary: ActivityDTO[];
    intentId?: number | null;
}>();

const model = defineModel<PlanActivity[]>({ required: true });

const search = ref('');
const adding = ref(false);

const byId = computed(
    () => new Map(props.activityLibrary.map((a) => [a.id, a])),
);

// Suggested activities for the chosen intent surface first; then the rest, filtered by search.
const candidates = computed(() => {
    const q = search.value.trim().toLowerCase();
    const selectedIds = new Set(model.value.map((m) => m.activity_id));
    const matchesSearch = (a: ActivityDTO) =>
        !q || a.name.toLowerCase().includes(q);
    const suggested = (a: ActivityDTO) =>
        props.intentId != null && (a.intent_ids ?? []).includes(props.intentId);

    return props.activityLibrary
        .filter((a) => !selectedIds.has(a.id) && matchesSearch(a))
        .sort(
            (a, b) =>
                Number(suggested(b)) - Number(suggested(a)) ||
                a.name.localeCompare(b.name),
        );
});

function add(activity: ActivityDTO) {
    model.value = [
        ...model.value,
        {
            activity_id: activity.id,
            estimated_minutes: activity.default_duration_minutes,
            order: model.value.length,
        },
    ];
    search.value = '';
    adding.value = false;
}

function remove(index: number) {
    model.value = model.value
        .filter((_, i) => i !== index)
        .map((m, i) => ({ ...m, order: i }));
}

function isSuggested(a: ActivityDTO): boolean {
    return (
        props.intentId != null && (a.intent_ids ?? []).includes(props.intentId)
    );
}

const totalMinutes = computed(() =>
    model.value.reduce((sum, m) => sum + (m.estimated_minutes || 0), 0),
);
</script>

<template>
    <div class="space-y-2">
        <div v-if="model.length" class="space-y-2">
            <div
                v-for="(entry, i) in model"
                :key="entry.activity_id"
                class="flex items-center gap-2 rounded-lg border border-border bg-card p-2"
            >
                <GripVertical class="size-4 shrink-0 text-muted-foreground" />
                <span class="min-w-0 flex-1 truncate text-sm">
                    {{ byId.get(entry.activity_id)?.name ?? 'Activity' }}
                </span>
                <Input
                    v-model.number="entry.estimated_minutes"
                    type="number"
                    min="1"
                    class="w-20"
                    aria-label="Estimated minutes"
                />
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Remove activity"
                    @click="remove(i)"
                >
                    <X class="size-4" />
                </Button>
            </div>
            <p class="text-right text-xs text-muted-foreground">
                Total estimate: {{ formatDuration(totalMinutes) }}
            </p>
        </div>

        <div v-if="adding" class="rounded-lg border border-border p-2">
            <Input
                v-model="search"
                placeholder="Search activities…"
                class="mb-2"
                autofocus
            />
            <div class="max-h-48 space-y-1 overflow-y-auto">
                <button
                    v-for="a in candidates"
                    :key="a.id"
                    type="button"
                    class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted"
                    @click="add(a)"
                >
                    <span>{{ a.name }}</span>
                    <span class="text-xs text-muted-foreground">
                        <span v-if="isSuggested(a)" class="mr-1 text-primary"
                            >suggested</span
                        >
                        {{ formatDuration(a.default_duration_minutes) }}
                    </span>
                </button>
                <p
                    v-if="!candidates.length"
                    class="px-2 py-1 text-sm text-muted-foreground"
                >
                    No matching activities.
                </p>
            </div>
        </div>

        <Button
            v-else
            type="button"
            variant="outline"
            size="sm"
            @click="adding = true"
        >
            <Plus class="mr-1 size-4" /> Add activity
        </Button>
    </div>
</template>
