<script setup lang="ts">
import { computed } from 'vue';
import { intentTint } from '@/design/tokens';
import type { IntentDTO } from '@/types/models';

const props = defineProps<{
    intent: Pick<IntentDTO, 'name' | 'color'>;
    weight?: number;
}>();

const style = computed(() => intentTint(props.intent.color));
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
        :style="style"
    >
        <span
            class="size-2 rounded-full"
            :style="{ backgroundColor: intent.color ?? 'currentColor' }"
        />
        {{ intent.name }}
        <span v-if="weight && weight > 1" class="opacity-70"
            >×{{ weight }}</span
        >
    </span>
</template>
