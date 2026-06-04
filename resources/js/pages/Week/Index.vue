<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarRange, ChevronLeft, ChevronRight } from '@lucide/vue';
import { addDays, format, parseISO } from 'date-fns';
import PageContainer from '@/components/cadence/PageContainer.vue';
import { Button } from '@/components/ui/button';
import type { PlanStatus } from '@/types/enums';

type Day = {
    date: string;
    weekday: string;
    day: number;
    template: string | null;
    plan_id: number | null;
    status: PlanStatus | null;
    blocks_count: number;
};

const props = defineProps<{ days: Day[]; weekStart: string; today: string }>();

const statusColor: Record<string, string> = {
    draft: 'bg-muted-foreground/40',
    generated: 'bg-amber-500',
    active: 'bg-emerald-500',
    done: 'bg-primary/60',
};

function shift(weeks: number) {
    const target = format(addDays(parseISO(props.weekStart), weeks * 7), 'yyyy-MM-dd');
    router.get('/week', { start: target }, { preserveState: true });
}

function go(day: Day) {
    router.visit(day.plan_id ? `/plans/${day.plan_id}` : '/plans');
}
</script>

<template>
    <Head title="Week" />

    <PageContainer title="Week" :icon="CalendarRange">
        <template #actions>
            <Button variant="outline" size="icon" aria-label="Previous week" @click="shift(-1)">
                <ChevronLeft class="size-4" />
            </Button>
            <Button variant="outline" size="icon" aria-label="Next week" @click="shift(1)">
                <ChevronRight class="size-4" />
            </Button>
        </template>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-7">
            <button
                v-for="day in days"
                :key="day.date"
                type="button"
                class="flex flex-col rounded-xl border p-3 text-left transition hover:shadow-sm"
                :class="day.date === today ? 'border-primary ring-1 ring-primary' : 'border-border'"
                @click="go(day)"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">{{ day.weekday }} {{ day.day }}</span>
                    <span v-if="day.status" class="size-2 rounded-full" :class="statusColor[day.status]" />
                </div>
                <span class="mt-1 truncate text-xs text-muted-foreground">
                    {{ day.template ?? 'No template' }}
                </span>
                <span class="mt-2 text-xs text-muted-foreground">{{ day.blocks_count }} blocks</span>
            </button>
        </div>

        <p class="mt-4 text-center">
            <Link href="/plans" class="text-sm text-muted-foreground hover:text-foreground">
                Open calendar →
            </Link>
        </p>
    </PageContainer>
</template>
