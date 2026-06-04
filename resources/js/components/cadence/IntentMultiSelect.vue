<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import IntentBadge from '@/components/cadence/IntentBadge.vue';
import type { IntentDTO } from '@/types/models';

export type DefaultIntent = { intent_id: number; weight: number };

const model = defineModel<DefaultIntent[]>({ required: true });

const page = usePage();
const library = computed<IntentDTO[]>(() => page.props.intentLibrary ?? []);

function isSelected(id: number): boolean {
    return model.value.some((d) => d.intent_id === id);
}

function toggle(intent: IntentDTO) {
    if (isSelected(intent.id)) {
        model.value = model.value.filter((d) => d.intent_id !== intent.id);
    } else {
        model.value = [...model.value, { intent_id: intent.id, weight: 1 }];
    }
}

function intentById(id: number): IntentDTO | undefined {
    return library.value.find((i) => i.id === id);
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="intent in library"
                :key="intent.id"
                type="button"
                class="min-h-[36px] rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="
                    isSelected(intent.id)
                        ? 'opacity-100'
                        : 'opacity-50 hover:opacity-80'
                "
                :aria-pressed="isSelected(intent.id)"
                @click="toggle(intent)"
            >
                <IntentBadge :intent="intent" />
            </button>
        </div>

        <p v-if="!library.length" class="text-sm text-muted-foreground">
            No intents in your library yet.
        </p>

        <!-- Per-selection weight controls. -->
        <div v-if="model.length" class="space-y-1 pt-1">
            <div
                v-for="entry in model"
                :key="entry.intent_id"
                class="flex items-center justify-between gap-2 text-sm"
            >
                <span>{{ intentById(entry.intent_id)?.name ?? 'Intent' }}</span>
                <label class="flex items-center gap-1 text-muted-foreground">
                    weight
                    <input
                        v-model.number="entry.weight"
                        type="number"
                        min="1"
                        max="100"
                        class="w-16 rounded-md border border-input bg-transparent px-2 py-1"
                    />
                </label>
            </div>
        </div>
    </div>
</template>
