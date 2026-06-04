<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    data: { label: string; value: number }[];
    suffix?: string;
    max?: number;
}>();

const max = computed(() => props.max ?? Math.max(1, ...props.data.map((d) => d.value)));
</script>

<template>
    <div class="space-y-2">
        <div v-for="d in data" :key="d.label" class="grid grid-cols-[6rem_1fr_auto] items-center gap-2 text-sm">
            <span class="truncate text-muted-foreground">{{ d.label }}</span>
            <div class="h-3 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: Math.round((d.value / max) * 100) + '%' }"
                />
            </div>
            <span class="tabular-nums text-muted-foreground">{{ d.value }}{{ suffix ?? '' }}</span>
        </div>
    </div>
</template>
