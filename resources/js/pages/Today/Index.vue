<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarSync, RefreshCw, Sun } from '@lucide/vue';
import { computed, ref } from 'vue';
import BlockActionsSheet from '@/components/cadence/BlockActionsSheet.vue';
import BlockDayView from '@/components/cadence/BlockDayView.vue';
import EmptyState from '@/components/cadence/EmptyState.vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import TimelineBoard from '@/components/cadence/TimelineBoard.vue';
import { Button } from '@/components/ui/button';
import { useRuntimeStore } from '@/stores/runtime';
import type { CheckInStatus } from '@/types/enums';
import type { DailyPlanDTO, PlanBlockComparison, ScheduleEventDTO } from '@/types/models';

const props = defineProps<{
    plan: DailyPlanDTO | null;
    comparisons: PlanBlockComparison[];
    nowMin: number;
    dayStartMin: number;
    date: string;
}>();

const runtime = useRuntimeStore();
const view = ref<'timeline' | 'blocks'>('timeline');

const events = computed<ScheduleEventDTO[]>(() => props.plan?.current_schedule?.events ?? []);

const blockStatus = computed<Record<number, CheckInStatus | null>>(() => {
    const map: Record<number, CheckInStatus | null> = {};
    (props.plan?.blocks ?? []).forEach((b) => (map[b.id] = b.current_status ?? null));

    return map;
});

function isFrozen(e: ScheduleEventDTO): boolean {
    return e.metadata?.frozen === true || (e.metadata?.end_min ?? 0) <= props.nowMin;
}

const sheetOpen = ref(false);
const selected = ref<ScheduleEventDTO | null>(null);
function openEvent(e: ScheduleEventDTO) {
    if (e.type !== 'block') {
return;
}

    selected.value = e;
    sheetOpen.value = true;
}
</script>

<template>
    <Head title="Today" />

    <PageContainer title="Today" :icon="Sun">
        <template v-if="plan && events.length" #actions>
            <div class="flex rounded-lg border border-border p-0.5 text-xs">
                <button
                    type="button"
                    class="rounded-md px-2 py-1"
                    :class="view === 'timeline' ? 'bg-muted font-medium' : 'text-muted-foreground'"
                    @click="view = 'timeline'"
                >
                    Timeline
                </button>
                <button
                    type="button"
                    class="rounded-md px-2 py-1"
                    :class="view === 'blocks' ? 'bg-muted font-medium' : 'text-muted-foreground'"
                    @click="view = 'blocks'"
                >
                    Blocks
                </button>
            </div>
            <Button variant="outline" size="sm" :disabled="runtime.inFlight" @click="runtime.runOp(plan.id, 'recompile')">
                <RefreshCw class="mr-1 size-4" /> Reflow
            </Button>
            <Button
                variant="outline"
                size="sm"
                @click="router.post(`/plans/${plan.id}/calendar/push`, {}, { preserveScroll: true })"
            >
                <CalendarSync class="mr-1 size-4" /> Sync
            </Button>
        </template>

        <EmptyState
            v-if="!plan"
            title="No plan for today"
            description="Head to the Plan tab to instantiate today from a template and generate your timeline."
        >
            <Button as-child><Link href="/plans">Plan today</Link></Button>
        </EmptyState>

        <EmptyState
            v-else-if="!events.length"
            title="Nothing generated yet"
            description="Open this day in the planner and tap “Generate day” to compile your timeline."
        >
            <Button as-child><Link :href="`/plans/${plan.id}`">Open planner</Link></Button>
        </EmptyState>

        <TimelineBoard
            v-else-if="view === 'timeline'"
            :events="events"
            :now-min="nowMin"
            :comparisons="comparisons"
            :block-status="blockStatus"
            @select="openEvent"
        />
        <BlockDayView v-else :events="events" :block-status="blockStatus" @select="openEvent" />

        <BlockActionsSheet
            v-if="plan"
            v-model:open="sheetOpen"
            :plan-id="plan.id"
            :event="selected"
            :status="selected?.source_block_id ? blockStatus[selected.source_block_id] : null"
            :frozen="selected ? isFrozen(selected) : false"
        />
    </PageContainer>
</template>
