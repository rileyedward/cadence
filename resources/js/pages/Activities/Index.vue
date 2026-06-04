<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Dumbbell, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DurationInput from '@/components/cadence/DurationInput.vue';
import IntentBadge from '@/components/cadence/IntentBadge.vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDuration } from '@/lib/time';
import type { ActivityDTO, IntentDTO } from '@/types/models';

defineProps<{ activities: ActivityDTO[] }>();

const page = usePage();
const library = computed<IntentDTO[]>(() => page.props.intentLibrary ?? []);

const dialogOpen = ref(false);
const editing = ref<ActivityDTO | null>(null);
const form = useForm<{
    name: string;
    default_duration_minutes: number;
    tags: string[];
    intent_ids: number[];
}>({ name: '', default_duration_minutes: 30, tags: [], intent_ids: [] });

const tagsText = ref('');

function openCreate() {
    editing.value = null;
    form.reset();
    form.default_duration_minutes = 30;
    tagsText.value = '';
    dialogOpen.value = true;
}

function openEdit(activity: ActivityDTO) {
    editing.value = activity;
    form.name = activity.name;
    form.default_duration_minutes = activity.default_duration_minutes;
    form.tags = activity.tags ?? [];
    form.intent_ids = (activity.intents ?? []).map((i) => i.id);
    tagsText.value = (activity.tags ?? []).join(', ');
    dialogOpen.value = true;
}

function toggleIntent(id: number) {
    form.intent_ids = form.intent_ids.includes(id)
        ? form.intent_ids.filter((x) => x !== id)
        : [...form.intent_ids, id];
}

function submit() {
    form.tags = tagsText.value
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);
    const opts = { onSuccess: () => (dialogOpen.value = false) };

    if (editing.value) {
        form.put(`/activities/${editing.value.id}`, opts);
    } else {
        form.post('/activities', opts);
    }
}

function destroy(activity: ActivityDTO) {
    if (confirm(`Delete "${activity.name}"?`)) {
        router.delete(`/activities/${activity.id}`);
    }
}
</script>

<template>
    <Head title="Activities" />

    <PageContainer title="Activities" :icon="Dumbbell">
        <template #actions>
            <Button size="sm" @click="openCreate"
                ><Plus class="mr-1 size-4" /> New activity</Button
            >
        </template>

        <div class="grid gap-2">
            <div
                v-for="activity in activities"
                :key="activity.id"
                class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ activity.name }}</span>
                        <span class="text-sm text-muted-foreground">
                            {{
                                formatDuration(
                                    activity.default_duration_minutes,
                                )
                            }}
                        </span>
                        <span
                            v-if="activity.is_system"
                            class="text-xs text-muted-foreground"
                            >· system</span
                        >
                    </div>
                    <div
                        v-if="activity.intents?.length"
                        class="mt-1 flex flex-wrap gap-1"
                    >
                        <IntentBadge
                            v-for="i in activity.intents"
                            :key="i.id"
                            :intent="i"
                        />
                    </div>
                </div>
                <div v-if="!activity.is_system" class="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Edit"
                        @click="openEdit(activity)"
                    >
                        <Pencil class="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Delete"
                        @click="destroy(activity)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit activity' : 'New activity'
                    }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="a-name">Name</Label>
                        <Input id="a-name" v-model="form.name" required />
                        <p
                            v-if="form.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Default duration</Label>
                        <DurationInput
                            v-model="form.default_duration_minutes"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="a-tags">Tags (comma separated)</Label>
                        <Input
                            id="a-tags"
                            v-model="tagsText"
                            placeholder="focus, leisure"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Suggested for intents</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="intent in library"
                                :key="intent.id"
                                type="button"
                                class="min-h-[36px] rounded-full"
                                :class="
                                    form.intent_ids.includes(intent.id)
                                        ? 'opacity-100'
                                        : 'opacity-50'
                                "
                                @click="toggleIntent(intent.id)"
                            >
                                <IntentBadge :intent="intent" />
                            </button>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing"
                            >Save</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PageContainer>
</template>
