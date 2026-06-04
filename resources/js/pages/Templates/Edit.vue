<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import BlockCard from '@/components/cadence/BlockCard.vue';
import BlockForm from '@/components/cadence/BlockForm.vue';
import type { BlockFormPayload } from '@/components/cadence/BlockForm.vue';
import PageContainer from '@/components/cadence/PageContainer.vue';
import TemplateAssignments from '@/components/cadence/TemplateAssignments.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { hhmmToMinutes } from '@/lib/time';
import type { BlockDTO, TemplateDTO } from '@/types/models';

export type BlockTypeDef = {
    key: string;
    name: string;
    defaults: {
        flexibility_mode?: string;
        constraints?: Record<string, unknown>;
        context?: Record<string, unknown>;
    };
};

const props = defineProps<{ template: TemplateDTO; blockTypes: BlockTypeDef[] }>();

const meta = useForm({
    name: props.template.name,
    description: props.template.description ?? '',
    is_active: props.template.is_active,
});

function saveMeta() {
    meta.put(`/templates/${props.template.id}`, { preserveScroll: true });
}

// --- Block editing ---------------------------------------------------------
const blockDialog = ref(false);
const editingBlock = ref<BlockDTO | null>(null);
const blockErrors = ref<Record<string, string>>({});

const blocks = computed<BlockDTO[]>(() =>
    [...(props.template.blocks ?? [])].sort((a, b) => a.order - b.order),
);

function openCreate() {
    editingBlock.value = null;
    blockErrors.value = {};
    blockDialog.value = true;
}

function openEdit(block: BlockDTO) {
    editingBlock.value = block;
    blockErrors.value = {};
    blockDialog.value = true;
}

function submitBlock(payload: BlockFormPayload) {
    const opts = {
        preserveScroll: true,
        onError: (e: Record<string, string>) => (blockErrors.value = e),
        onSuccess: () => (blockDialog.value = false),
    };

    if (editingBlock.value) {
        router.put(`/blocks/${editingBlock.value.id}`, payload, opts);
    } else {
        router.post(`/templates/${props.template.id}/blocks`, payload, opts);
    }
}

function removeBlock(block: BlockDTO) {
    if (confirm(`Remove "${block.name}"?`)) {
        router.delete(`/blocks/${block.id}`, { preserveScroll: true });
    }
}

function move(index: number, dir: -1 | 1) {
    const ordered = blocks.value.map((b) => b.id);
    const target = index + dir;

    if (target < 0 || target >= ordered.length) {
        return;
    }

    [ordered[index], ordered[target]] = [ordered[target], ordered[index]];
    router.post(
        `/templates/${props.template.id}/blocks/reorder`,
        { block_ids: ordered },
        { preserveScroll: true },
    );
}

// --- Mini day-strip preview (cosmetic; full timeline is doc 12) -------------
const strip = computed(() =>
    blocks.value.map((b) => {
        const start = hhmmToMinutes(b.start_time);
        let end = hhmmToMinutes(b.end_time);

        if (end <= start) {
            end += 1440;
        }

        return {
            id: b.id,
            name: b.name,
            left: (start / 1440) * 100,
            width: (Math.min(end - start, 1440) / 1440) * 100,
            mode: b.flexibility_mode,
        };
    }),
);

const stripColors: Record<string, string> = {
    strict: 'bg-destructive/70',
    soft: 'bg-amber-500/70',
    adaptive: 'bg-emerald-500/70',
};
</script>

<template>
    <Head :title="template.name" />

    <PageContainer>
        <div class="mb-4">
            <Button as-child variant="ghost" size="sm" class="-ml-2">
                <Link href="/templates"
                    ><ArrowLeft class="mr-1 size-4" /> Templates</Link
                >
            </Button>
        </div>

        <!-- Template meta -->
        <form
            class="mb-6 space-y-4 rounded-xl border border-border bg-card p-4"
            @submit.prevent="saveMeta"
        >
            <div class="grid gap-2">
                <Label for="t-name">Name</Label>
                <Input id="t-name" v-model="meta.name" required />
            </div>
            <div class="grid gap-2">
                <Label for="t-desc">Description</Label>
                <Input
                    id="t-desc"
                    v-model="meta.description"
                    placeholder="Optional"
                />
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="meta.is_active"
                        type="checkbox"
                        class="size-4"
                    />
                    Active
                </label>
                <Button type="submit" size="sm" :disabled="meta.processing"
                    >Save</Button
                >
            </div>
        </form>

        <!-- Day strip preview -->
        <div v-if="strip.length" class="mb-6">
            <div
                class="relative h-8 overflow-hidden rounded-md border border-border bg-muted/40"
            >
                <div
                    v-for="seg in strip"
                    :key="seg.id"
                    class="absolute top-0 h-full rounded-sm"
                    :class="stripColors[seg.mode]"
                    :style="{ left: seg.left + '%', width: seg.width + '%' }"
                    :title="seg.name"
                />
            </div>
            <div
                class="mt-1 flex justify-between text-xs text-muted-foreground"
            >
                <span>00:00</span><span>12:00</span><span>24:00</span>
            </div>
        </div>

        <!-- Blocks -->
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Blocks</h2>
            <Button size="sm" @click="openCreate"
                ><Plus class="mr-1 size-4" /> Add block</Button
            >
        </div>

        <p
            v-if="!blocks.length"
            class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
        >
            No blocks yet. Add your first time block to shape the day.
        </p>

        <div v-else class="grid gap-3">
            <BlockCard
                v-for="(block, i) in blocks"
                :key="block.id"
                :block="block"
                :first="i === 0"
                :last="i === blocks.length - 1"
                @edit="openEdit(block)"
                @remove="removeBlock(block)"
                @move-up="move(i, -1)"
                @move-down="move(i, 1)"
            />
        </div>

        <!-- Assignments -->
        <TemplateAssignments
            :template-id="template.id"
            :assignments="template.assignments ?? []"
            class="mt-8"
        />

        <Dialog v-model:open="blockDialog">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{
                        editingBlock ? 'Edit block' : 'Add block'
                    }}</DialogTitle>
                </DialogHeader>
                <BlockForm
                    :block="editingBlock"
                    :block-types="blockTypes"
                    :errors="blockErrors"
                    @submit="submitBlock"
                    @cancel="blockDialog = false"
                />
            </DialogContent>
        </Dialog>
    </PageContainer>
</template>
