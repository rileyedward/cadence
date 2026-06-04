<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

const model = defineModel<number>({ required: true });

const friendly = computed(() => {
    const m = model.value || 0;
    const h = Math.floor(m / 60);
    const min = m % 60;

    if (h && min) {
        return `${h}h ${min}m`;
    }

    if (h) {
        return `${h}h`;
    }

    return `${min}m`;
});

function onInput(event: Event) {
    const value = parseInt((event.target as HTMLInputElement).value, 10);
    model.value = Number.isNaN(value) ? 0 : Math.max(0, value);
}
</script>

<template>
    <div class="flex items-center gap-2">
        <Input
            type="number"
            inputmode="numeric"
            min="0"
            step="5"
            class="w-24"
            :value="model"
            @input="onInput"
        />
        <span class="text-sm text-muted-foreground">min · {{ friendly }}</span>
    </div>
</template>
