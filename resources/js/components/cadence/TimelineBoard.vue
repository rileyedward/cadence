<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { intentAccent } from '@/design/tokens';
import { createTimeScale } from '@/lib/timeScale';
import type { IntentDTO, PlanBlockComparison, ScheduleEventDTO } from '@/types/models';

const props = defineProps<{
    events: ScheduleEventDTO[];
    nowMin?: number | null;
    comparisons?: PlanBlockComparison[] | null;
    blockStatus?: Record<number, string | null>;
}>();

const emit = defineEmits<{ select: [ScheduleEventDTO] }>();

const page = usePage();
const library = computed<IntentDTO[]>(() => page.props.intentLibrary ?? []);

const originMin = computed(() =>
    props.events.length ? Math.min(...props.events.map((e) => e.metadata?.start_min ?? 0)) : 0,
);
const maxEnd = computed(() =>
    props.events.length ? Math.max(...props.events.map((e) => e.metadata?.end_min ?? 0)) : 0,
);
const scale = computed(() => createTimeScale(originMin.value, 1.1));
const totalHeight = computed(() => scale.value.height(originMin.value, maxEnd.value) + 16);

function intentColor(e: ScheduleEventDTO): string {
    const id = e.metadata?.intent_id;

    return intentAccent(library.value.find((i) => i.id === id)?.color);
}
function isFrozen(e: ScheduleEventDTO): boolean {
    return e.metadata?.frozen === true || (props.nowMin != null && (e.metadata?.end_min ?? 0) <= props.nowMin);
}

const comparisonByBlock = computed(() => {
    const map = new Map<number, PlanBlockComparison>();
    (props.comparisons ?? []).forEach((c) => map.set(c.daily_plan_block_id, c));

    return map;
});

const nowY = computed(() => (props.nowMin != null ? scale.value.y(props.nowMin) : -1));
const nowVisible = computed(() => props.nowMin != null && nowY.value >= 0 && nowY.value <= totalHeight.value);
</script>

<template>
    <div class="relative ml-2" :style="{ height: totalHeight + 'px' }">
        <div
            v-if="nowVisible"
            class="pointer-events-none absolute inset-x-0 z-10 flex items-center"
            :style="{ top: nowY + 'px' }"
        >
            <span class="-ml-2 size-2 rounded-full bg-red-500" />
            <span class="h-px flex-1 bg-red-500/60" />
        </div>

        <template v-for="event in events" :key="event.id">
            <button
                v-if="event.type === 'block'"
                type="button"
                class="absolute left-4 right-0 overflow-hidden rounded-lg border px-3 py-1.5 text-left transition"
                :class="isFrozen(event) ? 'opacity-60' : 'hover:shadow-sm'"
                :style="{
                    top: scale.y(event.metadata?.start_min ?? 0) + 'px',
                    height: scale.height(event.metadata?.start_min ?? 0, event.metadata?.end_min ?? 0) + 'px',
                    borderLeft: `4px solid ${intentColor(event)}`,
                }"
                @click="emit('select', event)"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm font-medium">{{ event.label }}</span>
                    <span
                        v-if="event.source_block_id && blockStatus?.[event.source_block_id]"
                        class="shrink-0 text-xs capitalize text-muted-foreground"
                    >
                        {{ blockStatus[event.source_block_id] }}
                    </span>
                </div>
                <span class="text-xs text-muted-foreground">
                    {{ event.start_time }}–{{ event.end_time }}
                </span>
            </button>

            <div
                v-else
                class="absolute left-4 right-0 flex items-center"
                :style="{
                    top: scale.y(event.metadata?.start_min ?? 0) + 'px',
                    height: scale.height(event.metadata?.start_min ?? 0, event.metadata?.end_min ?? 0) + 'px',
                }"
            >
                <span class="text-[10px] uppercase tracking-wide text-muted-foreground">
                    {{ event.metadata?.restInserted ? 'rest' : event.type }}
                </span>
            </div>
        </template>

        <!-- Actual-vs-planned ghosts: a solid bar where the block actually ran. -->
        <template v-if="comparisons">
            <div
                v-for="event in events.filter((e) => e.source_block_id && comparisonByBlock.get(e.source_block_id)?.actual_start_min != null)"
                :key="`actual-${event.id}`"
                class="pointer-events-none absolute left-1 w-1.5 rounded-full bg-emerald-500/70"
                :style="{
                    top: scale.y(comparisonByBlock.get(event.source_block_id!)!.actual_start_min!) + 'px',
                    height: scale.height(
                        comparisonByBlock.get(event.source_block_id!)!.actual_start_min!,
                        comparisonByBlock.get(event.source_block_id!)!.actual_end_min ?? comparisonByBlock.get(event.source_block_id!)!.actual_start_min!,
                    ) + 'px',
                }"
            />
        </template>
    </div>
</template>
