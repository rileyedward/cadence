<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, Pencil, Plus, Sparkles, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import type { IntentDTO } from '@/types/models';

const props = defineProps<{ intents: IntentDTO[] }>();

const system = computed(() => props.intents.filter((i) => i.is_system));
const custom = computed(() => props.intents.filter((i) => !i.is_system));

const dialogOpen = ref(false);
const editing = ref<IntentDTO | null>(null);
const form = useForm({ name: '', color: '#6366f1', icon: '', description: '' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.color = '#6366f1';
    dialogOpen.value = true;
}

function openEdit(intent: IntentDTO) {
    editing.value = intent;
    form.name = intent.name;
    form.color = intent.color ?? '#6366f1';
    form.icon = intent.icon ?? '';
    form.description = intent.description ?? '';
    dialogOpen.value = true;
}

function submit() {
    const opts = { onSuccess: () => (dialogOpen.value = false) };

    if (editing.value) {
        form.put(`/intents/${editing.value.id}`, opts);
    } else {
        form.post('/intents', opts);
    }
}

function clone(intent: IntentDTO) {
    router.post(`/intents/${intent.id}/clone`);
}

function destroy(intent: IntentDTO) {
    if (confirm(`Delete "${intent.name}"?`)) {
        router.delete(`/intents/${intent.id}`);
    }
}
</script>

<template>
    <Head title="Intents" />

    <PageContainer title="Intents" :icon="Sparkles">
        <template #actions>
            <Button size="sm" @click="openCreate"
                ><Plus class="mr-1 size-4" /> New intent</Button
            >
        </template>

        <section class="mb-8">
            <h2 class="mb-3 text-sm font-medium text-muted-foreground">
                Your intents
            </h2>
            <p v-if="!custom.length" class="text-sm text-muted-foreground">
                You haven't created any custom intents yet. Clone a system one
                or make your own.
            </p>
            <div v-else class="grid gap-2">
                <div
                    v-for="intent in custom"
                    :key="intent.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3"
                >
                    <IntentBadge :intent="intent" />
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Edit"
                            @click="openEdit(intent)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Delete"
                            @click="destroy(intent)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-medium text-muted-foreground">
                System library
            </h2>
            <div class="grid gap-2">
                <div
                    v-for="intent in system"
                    :key="intent.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3"
                >
                    <IntentBadge :intent="intent" />
                    <Button variant="ghost" size="sm" @click="clone(intent)">
                        <Copy class="mr-1 size-4" /> Customize
                    </Button>
                </div>
            </div>
        </section>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit intent' : 'New intent'
                    }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="i-name">Name</Label>
                        <Input id="i-name" v-model="form.name" required />
                        <p
                            v-if="form.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="flex items-end gap-3">
                        <div class="grid gap-2">
                            <Label for="i-color">Color</Label>
                            <input
                                id="i-color"
                                v-model="form.color"
                                type="color"
                                class="h-10 w-16 rounded-md border border-input"
                            />
                        </div>
                        <div class="grid flex-1 gap-2">
                            <Label for="i-icon">Icon (lucide name)</Label>
                            <Input
                                id="i-icon"
                                v-model="form.icon"
                                placeholder="sparkles"
                            />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="i-desc">Description</Label>
                        <Input
                            id="i-desc"
                            v-model="form.description"
                            placeholder="Optional"
                        />
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
