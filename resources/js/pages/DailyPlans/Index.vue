<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import {
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameMonth,
    parseISO,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import { computed, ref } from 'vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PlanStatus } from '@/types/enums';

type PlanSummary = {
    id: number;
    date: string;
    status: PlanStatus;
    template_id: number | null;
};
type TemplateSummary = { id: number; name: string };

const props = defineProps<{
    plans: PlanSummary[];
    templates: TemplateSummary[];
    today: string;
}>();

const cursor = ref(startOfMonth(parseISO(props.today)));

const plansByDate = computed(() => {
    const map = new Map<string, PlanSummary>();
    props.plans.forEach((p) => map.set(p.date, p));

    return map;
});

const days = computed(() => {
    const start = startOfWeek(startOfMonth(cursor.value));
    const end = endOfWeek(endOfMonth(cursor.value));

    return eachDayOfInterval({ start, end });
});

const statusColor: Record<string, string> = {
    draft: 'bg-muted-foreground/40',
    generated: 'bg-amber-500',
    active: 'bg-emerald-500',
    done: 'bg-primary/60',
};

// --- Instantiation dialog --------------------------------------------------
const dialogOpen = ref(false);
const selectedDate = ref('');
const form = useForm<{ date: string; template_id: string }>({
    date: '',
    template_id: '',
});

function onDay(date: Date) {
    const iso = format(date, 'yyyy-MM-dd');
    const existing = plansByDate.value.get(iso);

    if (existing) {
        router.visit(`/plans/${existing.id}`);

        return;
    }

    selectedDate.value = iso;
    form.date = iso;
    form.template_id = props.templates[0]?.id
        ? String(props.templates[0].id)
        : '';
    dialogOpen.value = true;
}

function instantiate() {
    form.transform((data) => ({
        date: data.date,
        template_id: data.template_id ? Number(data.template_id) : null,
    })).post('/plans', { onSuccess: () => (dialogOpen.value = false) });
}

const weekdayLabels = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
</script>

<template>
    <Head title="Plan" />

    <PageContainer title="Plan" :icon="CalendarDays">
        <template #actions>
            <Link href="/week" class="text-sm text-muted-foreground hover:text-foreground">
                Week view
            </Link>
        </template>

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-medium">
                {{ format(cursor, 'MMMM yyyy') }}
            </h2>
            <div class="flex gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Previous month"
                    @click="cursor = addMonths(cursor, -1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Next month"
                    @click="cursor = addMonths(cursor, 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>

        <div
            class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground"
        >
            <div v-for="(label, i) in weekdayLabels" :key="i" class="py-1">
                {{ label }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <button
                v-for="day in days"
                :key="day.toISOString()"
                type="button"
                class="flex aspect-square min-h-[44px] flex-col items-center justify-center rounded-lg border text-sm transition-colors hover:bg-muted"
                :class="[
                    isSameMonth(day, cursor)
                        ? 'border-border'
                        : 'border-transparent text-muted-foreground/50',
                    format(day, 'yyyy-MM-dd') === today
                        ? 'ring-2 ring-primary'
                        : '',
                ]"
                @click="onDay(day)"
            >
                <span>{{ format(day, 'd') }}</span>
                <span
                    v-if="plansByDate.get(format(day, 'yyyy-MM-dd'))"
                    class="mt-1 size-1.5 rounded-full"
                    :class="
                        statusColor[
                            plansByDate.get(format(day, 'yyyy-MM-dd'))!.status
                        ]
                    "
                />
            </button>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Plan {{ selectedDate }}</DialogTitle>
                </DialogHeader>

                <div v-if="templates.length" class="grid gap-2">
                    <label class="text-sm text-muted-foreground"
                        >Template</label
                    >
                    <Select v-model="form.template_id">
                        <SelectTrigger
                            ><SelectValue placeholder="Choose a template"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="t in templates"
                                :key="t.id"
                                :value="String(t.id)"
                            >
                                {{ t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Or leave blank to plan a blank day.
                    </p>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    You have no active templates — a blank day will be created.
                    You can build a template anytime from the Templates tab.
                </p>

                <DialogFooter>
                    <Button :disabled="form.processing" @click="instantiate"
                        >Create day</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PageContainer>
</template>
