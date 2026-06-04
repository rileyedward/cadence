<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title?: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="mx-auto w-full max-w-3xl px-4 py-6">
        <header
            v-if="title || $slots.actions"
            class="mb-6 flex items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3">
                <component :is="icon" v-if="icon" class="size-6 text-primary" />
                <h1 v-if="title" class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <slot />
    </div>
</template>
