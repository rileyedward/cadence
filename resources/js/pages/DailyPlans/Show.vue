<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ChevronLeft, ChevronRight, Sparkles } from '@lucide/vue';
import { format, parseISO } from 'date-fns';
import { computed, onMounted, ref, watch } from 'vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import PlanBlockCard from '@/components/cadence/PlanBlockCard.vue';
import type { BlockDecision } from '@/components/cadence/PlanBlockCard.vue';
import PlanPreview from '@/components/cadence/PlanPreview.vue';
import type { PreviewEvent } from '@/components/cadence/PlanPreview.vue';
import SuggestionsPanel from '@/components/cadence/SuggestionsPanel.vue';
import type { Recommendation } from '@/components/cadence/SuggestionsPanel.vue';
import { Button } from '@/components/ui/button';
import { getJson, postJson } from '@/lib/http';
import type {
    ActivityDTO,
    DailyPlanBlockDTO,
    DailyPlanDTO,
} from '@/types/models';

type QuickFill = {
    block_id: number;
    intent_id: number | null;
    activity_ids: number[];
};
type Insights = {
    quick_fill: QuickFill[];
    energy: unknown[];
    recommendations: Recommendation[];
};

const props = defineProps<{
    plan: DailyPlanDTO;
    activityLibrary: ActivityDTO[];
}>();

function toDecision(b: DailyPlanBlockDTO): BlockDecision {
    return {
        id: b.id,
        intent_id: b.intent_id,
        secondary_intent_id: b.secondary_intent_id,
        energy_level: b.energy_level,
        focus_intensity: b.focus_intensity,
        social_context: b.social_context,
        mobility_preference: b.mobility_preference,
        context_tags: b.context_tags,
        activities: (b.activities ?? []).map((a, i) => ({
            activity_id: a.id,
            estimated_minutes: a.pivot.estimated_minutes,
            order: a.pivot.order ?? i,
        })),
    };
}

const blocks = computed(() => props.plan.blocks ?? []);
const decisions = ref<BlockDecision[]>(blocks.value.map(toDecision));
const step = ref(0);
const saving = ref(false);

// Live, debounced server preview (doc 07) — compiles the in-progress decisions
// without persisting them. Falls back to the saved schedule inside PlanPreview.
const previewEvents = ref<PreviewEvent[] | null>(null);
let previewTimer: ReturnType<typeof setTimeout> | undefined;

async function refreshPreview() {
    if (!blocks.value.length) {
return;
}

    try {
        const data = await postJson<{ events: PreviewEvent[] }>(
            `/plans/${props.plan.id}/preview`,
            { blocks: decisions.value },
        );
        previewEvents.value = data.events;
    } catch {
        previewEvents.value = null; // fall back to the persisted schedule
    }
}

watch(
    decisions,
    () => {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 500);
    },
    { deep: true },
);

onMounted(refreshPreview);

// Local, deterministic intelligence (doc 10): habit quick-fill + recommendations.
const insights = ref<Insights | null>(null);
const canQuickFill = computed(
    () => insights.value?.quick_fill.some((q) => q.intent_id !== null) ?? false,
);

async function loadInsights() {
    try {
        insights.value = await getJson<Insights>(`/plans/${props.plan.id}/insights`);
    } catch {
        insights.value = null;
    }
}

function applyQuickFill() {
    const byId = new Map(props.activityLibrary.map((a) => [a.id, a]));

    for (const q of insights.value?.quick_fill ?? []) {
        const decision = decisions.value.find((d) => d.id === q.block_id);

        if (!decision) {
continue;
}

        if (q.intent_id !== null) {
decision.intent_id = q.intent_id;
}

        if (q.activity_ids.length && !decision.activities.length) {
            decision.activities = q.activity_ids.map((id, i) => ({
                activity_id: id,
                estimated_minutes: byId.get(id)?.default_duration_minutes ?? 30,
                order: i,
            }));
        }
    }
}

onMounted(loadInsights);

function save(onDone?: () => void) {
    saving.value = true;
    router.put(
        `/plans/${props.plan.id}`,
        { blocks: decisions.value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                saving.value = false;
                onDone?.();
            },
        },
    );
}

// Generate (server compiler) is wired in Phase 3; available once the plan has blocks.
function generate() {
    save(() =>
        router.post(
            `/plans/${props.plan.id}/generate`,
            {},
            { preserveScroll: true },
        ),
    );
}

const heading = computed(() =>
    format(parseISO(props.plan.date), 'EEEE, MMM d'),
);
</script>

<template>
    <Head :title="heading" />

    <PageContainer>
        <div class="mb-4 flex items-center justify-between">
            <Button as-child variant="ghost" size="sm" class="-ml-2">
                <Link href="/plans"
                    ><ArrowLeft class="mr-1 size-4" /> Plan</Link
                >
            </Button>
            <span
                class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium capitalize"
            >
                {{ plan.status }}
            </span>
        </div>

        <h1 class="mb-4 text-2xl font-semibold tracking-tight">
            {{ heading }}
        </h1>

        <SuggestionsPanel
            v-if="insights"
            class="mb-6"
            :recommendations="insights.recommendations"
            :can-quick-fill="canQuickFill"
            @quick-fill="applyQuickFill"
        />

        <div
            v-if="!blocks.length"
            class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
        >
            <p class="font-medium text-foreground">This is a blank day.</p>
            <p class="mt-1 text-sm">
                No blocks to decide on. You can still generate an empty
                schedule.
            </p>
        </div>

        <template v-else>
            <!-- Desktop: two-pane (cards left, live preview right) -->
            <div class="hidden gap-6 lg:grid lg:grid-cols-2">
                <div class="space-y-4">
                    <PlanBlockCard
                        v-for="(block, i) in blocks"
                        :key="block.id"
                        v-model="decisions[i]"
                        :block="block"
                        :activity-library="activityLibrary"
                    />
                </div>
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <PlanPreview :plan="plan" :decisions="decisions" :preview-events="previewEvents" />
                </div>
            </div>

            <!-- Mobile: stepper, one card at a time -->
            <div class="lg:hidden">
                <PlanBlockCard
                    v-if="blocks[step]"
                    v-model="decisions[step]"
                    :block="blocks[step]"
                    :activity-library="activityLibrary"
                />
                <div class="mt-4 flex items-center justify-between">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="step === 0"
                        @click="step--"
                    >
                        <ChevronLeft class="mr-1 size-4" /> Back
                    </Button>
                    <span class="text-sm text-muted-foreground"
                        >{{ step + 1 }} / {{ blocks.length }}</span
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="step === blocks.length - 1"
                        @click="step++"
                    >
                        Next <ChevronRight class="ml-1 size-4" />
                    </Button>
                </div>

                <details class="mt-4 rounded-xl border border-border">
                    <summary class="cursor-pointer p-3 text-sm font-medium">
                        Timeline preview
                    </summary>
                    <div class="border-t border-border p-3">
                        <PlanPreview :plan="plan" :decisions="decisions" :preview-events="previewEvents" />
                    </div>
                </details>
            </div>
        </template>

        <!-- Sticky action bar -->
        <div class="sticky bottom-16 z-10 mt-6 flex gap-2 lg:bottom-0">
            <Button
                variant="outline"
                class="flex-1"
                :disabled="saving"
                @click="save()"
            >
                Save decisions
            </Button>
            <Button class="flex-1" :disabled="saving" @click="generate">
                <Sparkles class="mr-1 size-4" /> Generate day
            </Button>
        </div>
    </PageContainer>
</template>
