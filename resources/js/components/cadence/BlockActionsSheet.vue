<script setup lang="ts">
import { Minus, Plus, RefreshCw, SkipForward } from '@lucide/vue';
import CheckInControls from '@/components/cadence/CheckInControls.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useRuntimeStore } from '@/stores/runtime';
import type { CheckInStatus } from '@/types/enums';
import type { ScheduleEventDTO } from '@/types/models';

const props = defineProps<{
    planId: number;
    event: ScheduleEventDTO | null;
    status?: CheckInStatus | null;
    frozen?: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });

const runtime = useRuntimeStore();

function op(name: string, payload: Record<string, string | number | boolean | null> = {}) {
    if (!props.event?.source_block_id) {
return;
}

    runtime.runOp(props.planId, name, { block_id: props.event.source_block_id, ...payload });
    open.value = false;
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent side="bottom" class="safe-bottom">
            <SheetHeader>
                <SheetTitle>{{ event?.label ?? 'Block' }}</SheetTitle>
            </SheetHeader>

            <div v-if="event" class="space-y-4 p-4">
                <p class="text-sm text-muted-foreground">
                    {{ event.start_time }}–{{ event.end_time }}
                </p>

                <div v-if="frozen" class="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                    This block is in the past or already started — it's locked.
                </div>

                <template v-else-if="event.source_block_id">
                    <CheckInControls
                        :plan-id="planId"
                        :block-id="event.source_block_id"
                        :current-status="status"
                    />

                    <div class="grid grid-cols-2 gap-2">
                        <Button variant="outline" @click="op('extend', { delta: 15 })">
                            <Plus class="mr-1 size-4" /> +15 min
                        </Button>
                        <Button variant="outline" @click="op('shorten', { delta: 15 })">
                            <Minus class="mr-1 size-4" /> −15 min
                        </Button>
                        <Button variant="outline" @click="op('skip')">
                            <SkipForward class="mr-1 size-4" /> Skip
                        </Button>
                        <Button variant="outline" @click="op('recompile')">
                            <RefreshCw class="mr-1 size-4" /> Reflow
                        </Button>
                    </div>
                </template>
            </div>
        </SheetContent>
    </Sheet>
</template>
