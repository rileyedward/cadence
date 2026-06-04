<script setup lang="ts">
import { Lightbulb, Wand2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

export type Recommendation = {
    kind: string;
    message: string;
    target: Record<string, unknown>;
    action: Record<string, unknown> | null;
};

defineProps<{
    recommendations: Recommendation[];
    canQuickFill: boolean;
}>();

defineEmits<{ quickFill: [] }>();
</script>

<template>
    <div v-if="canQuickFill || recommendations.length" class="space-y-3 rounded-xl border border-border bg-card p-4">
        <div class="flex items-center justify-between gap-2">
            <h3 class="flex items-center gap-1.5 text-sm font-medium">
                <Lightbulb class="size-4 text-primary" /> Suggestions
            </h3>
            <Button v-if="canQuickFill" size="sm" variant="outline" @click="$emit('quickFill')">
                <Wand2 class="mr-1 size-4" /> Quick-fill from habits
            </Button>
        </div>

        <ul v-if="recommendations.length" class="space-y-2">
            <li
                v-for="(rec, i) in recommendations"
                :key="i"
                class="rounded-lg bg-muted/50 p-2.5 text-sm text-muted-foreground"
            >
                {{ rec.message }}
            </li>
        </ul>
    </div>
</template>
