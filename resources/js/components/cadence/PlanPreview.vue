<script setup lang="ts">
import { computed } from 'vue';
import type { BlockDecision } from '@/components/cadence/PlanBlockCard.vue';
import { formatDuration } from '@/lib/time';
import type { DailyPlanDTO } from '@/types/models';

export type PreviewEvent = {
    type: string;
    label: string;
    start_time: string;
    end_time: string;
    metadata?: Record<string, unknown> | null;
};

const props = defineProps<{
    plan: DailyPlanDTO;
    decisions: BlockDecision[];
    previewEvents?: PreviewEvent[] | null;
}>();

// Prefer the live (debounced) server preview; fall back to the persisted schedule.
const events = computed(
    () => props.previewEvents ?? props.plan.current_schedule?.events ?? [],
);

const blocks = computed(() => props.plan.blocks ?? []);

function estimateFor(blockId: number): number {
    const d = props.decisions.find((x) => x.id === blockId);

    return (d?.activities ?? []).reduce(
        (s, a) => s + (a.estimated_minutes || 0),
        0,
    );
}
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4">
        <h3 class="mb-3 text-sm font-medium text-muted-foreground">
            Timeline preview
        </h3>

        <!-- Authoritative schedule once generated -->
        <div v-if="events.length" class="space-y-1.5">
            <div
                v-for="(event, i) in events"
                :key="i"
                class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm"
                :class="
                    event.type === 'block'
                        ? 'bg-muted/60'
                        : 'text-muted-foreground'
                "
            >
                <span>{{ event.label }}</span>
                <span class="tabular-nums"
                    >{{ event.start_time }}–{{ event.end_time }}</span
                >
            </div>
        </div>

        <!-- Pre-generation estimate -->
        <div v-else class="space-y-1.5">
            <div
                v-for="block in blocks"
                :key="block.id"
                class="flex items-center justify-between rounded-md bg-muted/40 px-2 py-1.5 text-sm"
            >
                <span>{{ block.name }}</span>
                <span class="text-muted-foreground">
                    {{ block.start_time }}–{{ block.end_time }}
                    <template v-if="estimateFor(block.id)">
                        · {{ formatDuration(estimateFor(block.id)) }}
                    </template>
                </span>
            </div>
            <p class="pt-2 text-xs text-muted-foreground">
                Generate the day to compile buffers, rest, and reflow into an
                exact timeline.
            </p>
        </div>
    </div>
</template>
