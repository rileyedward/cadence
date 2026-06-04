<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, LayoutTemplate, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/cadence/EmptyState.vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TemplateDTO } from '@/types/models';

defineProps<{ templates: TemplateDTO[] }>();

const createOpen = ref(false);
const form = useForm({ name: '', description: '' });

function create() {
    form.post('/templates', {
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
}

function fork(template: TemplateDTO) {
    router.post(`/templates/${template.id}/fork`);
}

function destroy(template: TemplateDTO) {
    if (confirm(`Delete "${template.name}"? This cannot be undone.`)) {
        router.delete(`/templates/${template.id}`);
    }
}
</script>

<template>
    <Head title="Templates" />

    <PageContainer title="Templates" :icon="LayoutTemplate">
        <template #actions>
            <Link
                href="/intents"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Intents
            </Link>
            <Link
                href="/activities"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Activities
            </Link>
            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button size="sm"><Plus class="mr-1 size-4" /> New</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader
                        ><DialogTitle>New template</DialogTitle></DialogHeader
                    >
                    <form class="space-y-4" @submit.prevent="create">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="Weekday"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Optional"
                            />
                        </div>
                        <DialogFooter>
                            <Button type="submit" :disabled="form.processing"
                                >Create</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </template>

        <EmptyState
            v-if="!templates.length"
            title="No templates yet"
            description="Templates are reusable weekly structures made of time blocks. Create one to start shaping your week."
        />

        <div v-else class="grid gap-3">
            <div
                v-for="template in templates"
                :key="template.id"
                class="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div class="min-w-0">
                    <h3 class="truncate font-medium">{{ template.name }}</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ template.blocks_count ?? 0 }} blocks
                        <span v-for="a in template.assignments" :key="a.id">
                            · {{ a.scope }}
                        </span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <Button
                        as-child
                        variant="ghost"
                        size="icon"
                        aria-label="Edit"
                    >
                        <Link :href="`/templates/${template.id}/edit`"
                            ><Pencil class="size-4"
                        /></Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Fork"
                        @click="fork(template)"
                    >
                        <Copy class="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Delete"
                        @click="destroy(template)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
