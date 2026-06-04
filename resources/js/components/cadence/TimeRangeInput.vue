<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

const start = defineModel<string>('start', { required: true });
const end = defineModel<string>('end', { required: true });

// Surface a hint when the window wraps past clock-midnight (valid for the logical day).
const wraps = computed(() => end.value < start.value);
</script>

<template>
    <div class="flex items-center gap-2">
        <Input
            type="time"
            v-model="start"
            class="w-32"
            aria-label="Start time"
        />
        <span class="text-muted-foreground">–</span>
        <Input type="time" v-model="end" class="w-32" aria-label="End time" />
        <span v-if="wraps" class="text-xs text-muted-foreground">next day</span>
    </div>
</template>
