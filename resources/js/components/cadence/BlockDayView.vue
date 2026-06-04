<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { intentTint } from '@/design/tokens';
import type { IntentDTO, ScheduleEventDTO } from '@/types/models';

const props = defineProps<{
    events: ScheduleEventDTO[];
    blockStatus?: Record<number, string | null>;
}>();

const emit = defineEmits<{ select: [ScheduleEventDTO] }>();

const page = usePage();
const library = computed<IntentDTO[]>(() => page.props.intentLibrary ?? []);

// Intent-first lens: only the block events, as cards (less time-literal).
const blockEvents = computed(() => props.events.filter((e) => e.type === 'block'));

function tint(e: ScheduleEventDTO): Record<string, string> {
    const id = e.metadata?.intent_id;

    return intentTint(library.value.find((i) => i.id === id)?.color);
}
</script>

<template>
    <div class="grid gap-2">
        <button
            v-for="event in blockEvents"
            :key="event.id"
            type="button"
            class="rounded-xl border p-4 text-left transition hover:shadow-sm"
            :style="tint(event)"
            @click="emit('select', event)"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium">{{ event.label }}</span>
                <span
                    v-if="event.source_block_id && blockStatus?.[event.source_block_id]"
                    class="text-xs capitalize opacity-70"
                >
                    {{ blockStatus[event.source_block_id] }}
                </span>
            </div>
            <span class="text-sm opacity-70">{{ event.start_time }}–{{ event.end_time }}</span>
        </button>
    </div>
</template>
