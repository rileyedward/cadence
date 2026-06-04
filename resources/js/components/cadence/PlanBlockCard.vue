<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivityPicker from '@/components/cadence/ActivityPicker.vue';
import type { PlanActivity } from '@/components/cadence/ActivityPicker.vue';
import IntentBadge from '@/components/cadence/IntentBadge.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    EnergyLevel,
    FocusIntensity,
    MobilityPref,
    SocialContext,
} from '@/types/enums';
import type { ActivityDTO, DailyPlanBlockDTO, IntentDTO } from '@/types/models';

export type BlockDecision = {
    id: number;
    intent_id: number | null;
    secondary_intent_id: number | null;
    energy_level: string | null;
    focus_intensity: string | null;
    social_context: string | null;
    mobility_preference: string | null;
    context_tags: string[] | null;
    activities: PlanActivity[];
};

defineProps<{
    block: DailyPlanBlockDTO;
    activityLibrary: ActivityDTO[];
}>();

const decision = defineModel<BlockDecision>({ required: true });

const page = usePage();
const library = computed<IntentDTO[]>(() => page.props.intentLibrary ?? []);

function pickPrimary(id: number) {
    decision.value.intent_id = decision.value.intent_id === id ? null : id;
}

// Select components bind strings; map null <-> '' for the optional selectors.
function selectModel(key: keyof BlockDecision) {
    return computed({
        get: () => (decision.value[key] as string | null) ?? '',
        set: (v: string) =>
            ((decision.value[key] as string | null) = v || null),
    });
}

const energy = selectModel('energy_level');
const focus = selectModel('focus_intensity');
const social = selectModel('social_context');
const mobility = selectModel('mobility_preference');
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4">
        <header class="mb-3">
            <h3 class="font-medium">{{ block.name }}</h3>
            <p class="text-sm text-muted-foreground">
                {{ block.start_time }}–{{ block.end_time }} ·
                {{ block.flexibility_mode }}
            </p>
        </header>

        <div class="space-y-4">
            <!-- Primary intent -->
            <div>
                <p class="mb-1.5 text-sm font-medium">What state are you in?</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="intent in library"
                        :key="intent.id"
                        type="button"
                        class="min-h-[36px] rounded-full"
                        :class="
                            decision.intent_id === intent.id
                                ? 'opacity-100 ring-2 ring-ring'
                                : 'opacity-50'
                        "
                        @click="pickPrimary(intent.id)"
                    >
                        <IntentBadge :intent="intent" />
                    </button>
                </div>
            </div>

            <!-- Activities -->
            <div>
                <p class="mb-1.5 text-sm font-medium">Activities</p>
                <ActivityPicker
                    v-model="decision.activities"
                    :activity-library="activityLibrary"
                    :intent-id="decision.intent_id"
                />
            </div>

            <!-- Selectors -->
            <div class="grid grid-cols-2 gap-3">
                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Energy</span>
                    <Select v-model="energy">
                        <SelectTrigger
                            ><SelectValue placeholder="—"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="v in EnergyLevel"
                                :key="v"
                                :value="v"
                                >{{ v }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Focus</span>
                    <Select v-model="focus">
                        <SelectTrigger
                            ><SelectValue placeholder="—"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="v in FocusIntensity"
                                :key="v"
                                :value="v"
                                >{{ v }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Social</span>
                    <Select v-model="social">
                        <SelectTrigger
                            ><SelectValue placeholder="—"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="v in SocialContext"
                                :key="v"
                                :value="v"
                                >{{ v }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Mobility</span>
                    <Select v-model="mobility">
                        <SelectTrigger
                            ><SelectValue placeholder="—"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="v in MobilityPref"
                                :key="v"
                                :value="v"
                                >{{ v }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </label>
            </div>
        </div>
    </div>
</template>
