<script setup lang="ts">
import { ArrowDown, ArrowUp, Pencil, Trash2 } from '@lucide/vue';
import IntentBadge from '@/components/cadence/IntentBadge.vue';
import { Button } from '@/components/ui/button';
import type { BlockDTO } from '@/types/models';

defineProps<{
    block: BlockDTO;
    first?: boolean;
    last?: boolean;
}>();

defineEmits<{ edit: []; remove: []; moveUp: []; moveDown: [] }>();

const modeStyles: Record<string, string> = {
    strict: 'bg-destructive/10 text-destructive',
    soft: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    adaptive: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
};
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="truncate font-medium">{{ block.name }}</h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="modeStyles[block.flexibility_mode]"
                    >
                        {{ block.flexibility_mode }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ block.start_time }}–{{ block.end_time }}
                    <span v-if="block.category"> · {{ block.category }}</span>
                </p>
                <div
                    v-if="block.default_intents?.length"
                    class="mt-2 flex flex-wrap gap-1.5"
                >
                    <IntentBadge
                        v-for="intent in block.default_intents"
                        :key="intent.id"
                        :intent="intent"
                        :weight="intent.weight"
                    />
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    :disabled="first"
                    aria-label="Move up"
                    @click="$emit('moveUp')"
                >
                    <ArrowUp class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    :disabled="last"
                    aria-label="Move down"
                    @click="$emit('moveDown')"
                >
                    <ArrowDown class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Edit block"
                    @click="$emit('edit')"
                >
                    <Pencil class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Remove block"
                    @click="$emit('remove')"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
