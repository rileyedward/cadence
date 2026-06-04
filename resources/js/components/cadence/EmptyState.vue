<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center rounded-xl border border-dashed border-border p-8 text-center"
    >
        <component
            :is="icon"
            v-if="icon"
            class="mb-3 size-8 text-muted-foreground"
        />
        <p class="font-medium text-foreground">{{ title }}</p>
        <p
            v-if="description"
            class="mt-1 max-w-sm text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
