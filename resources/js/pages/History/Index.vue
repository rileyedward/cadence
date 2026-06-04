<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart3 } from '@lucide/vue';
import { computed } from 'vue';
import BarChart from '@/components/cadence/BarChart.vue';
import EmptyState from '@/components/cadence/EmptyState.vue';
import PageContainer from '@/components/cadence/PageContainer.vue';

type Patterns = {
    completion_by_intent: Record<string, { completed: number; total: number; rate: number }>;
    skip_by_block: Record<string, number>;
    drift_by_block: Record<string, { samples: number; avg_drift_min: number }>;
    energy_distribution: Record<string, number>;
};
type Recommendation = { kind: string; message: string };

const props = defineProps<{
    patterns: Patterns;
    recommendations: Recommendation[];
    hasHistory: boolean;
}>();

const completion = computed(() =>
    Object.entries(props.patterns.completion_by_intent).map(([label, v]) => ({
        label,
        value: Math.round(v.rate * 100),
    })),
);
const energy = computed(() =>
    Object.entries(props.patterns.energy_distribution).map(([label, value]) => ({ label, value })),
);
const skips = computed(() =>
    Object.entries(props.patterns.skip_by_block).map(([label, value]) => ({ label, value })),
);
const drift = computed(() =>
    Object.entries(props.patterns.drift_by_block).map(([label, v]) => ({
        label,
        value: v.avg_drift_min,
    })),
);
</script>

<template>
    <Head title="Insights" />

    <PageContainer title="Insights" :icon="BarChart3">
        <EmptyState
            v-if="!hasHistory"
            title="Not enough data yet"
            description="Once you have a few days of check-ins, your completion rates, skip patterns, and timing drift will show up here."
        />

        <div v-else class="grid gap-6 lg:grid-cols-2">
            <section v-if="completion.length" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-medium">Completion rate by intent</h2>
                <BarChart :data="completion" suffix="%" :max="100" />
            </section>

            <section v-if="energy.length" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-medium">Energy distribution</h2>
                <BarChart :data="energy" />
            </section>

            <section v-if="skips.length" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-medium">Most-skipped blocks</h2>
                <BarChart :data="skips" suffix="×" />
            </section>

            <section v-if="drift.length" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-medium">Avg duration drift (min)</h2>
                <ul class="space-y-1.5 text-sm">
                    <li v-for="d in drift" :key="d.label" class="flex justify-between">
                        <span class="text-muted-foreground">{{ d.label }}</span>
                        <span :class="d.value > 0 ? 'text-amber-600' : 'text-emerald-600'">
                            {{ d.value > 0 ? '+' : '' }}{{ d.value }}
                        </span>
                    </li>
                </ul>
            </section>

            <section
                v-if="recommendations.length"
                class="rounded-xl border border-border bg-card p-4 lg:col-span-2"
            >
                <h2 class="mb-3 text-sm font-medium">Recommendations</h2>
                <ul class="space-y-2">
                    <li
                        v-for="(rec, i) in recommendations"
                        :key="i"
                        class="rounded-lg bg-muted/50 p-2.5 text-sm text-muted-foreground"
                    >
                        {{ rec.message }}
                    </li>
                </ul>
            </section>
        </div>
    </PageContainer>
</template>
