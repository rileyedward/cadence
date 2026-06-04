<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Play, SkipForward } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useRuntimeStore } from '@/stores/runtime';
import type { CheckInStatus } from '@/types/enums';

const props = defineProps<{
    planId: number;
    blockId: number;
    currentStatus?: CheckInStatus | null;
}>();

const runtime = useRuntimeStore();

function checkIn(status: CheckInStatus, recompile = false) {
    router.post(
        `/plan-blocks/${props.blockId}/check-in`,
        { status },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Starting a block reflows the rest of the day from the real now.
                if (recompile) {
runtime.runOp(props.planId, 'recompile');
}
            },
        },
    );
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            v-if="currentStatus !== 'started' && currentStatus !== 'completed'"
            size="sm"
            variant="outline"
            @click="checkIn('started', true)"
        >
            <Play class="mr-1 size-4" /> Start
        </Button>
        <Button
            v-if="currentStatus === 'started'"
            size="sm"
            @click="checkIn('completed')"
        >
            <Check class="mr-1 size-4" /> Complete
        </Button>
        <Button
            v-if="currentStatus !== 'completed' && currentStatus !== 'skipped'"
            size="sm"
            variant="ghost"
            @click="checkIn('skipped', true)"
        >
            <SkipForward class="mr-1 size-4" /> Skip
        </Button>
        <span
            v-if="currentStatus"
            class="self-center text-xs font-medium capitalize text-muted-foreground"
        >
            {{ currentStatus }}
        </span>
    </div>
</template>
