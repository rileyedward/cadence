// Interfaces mirroring backend API resources (doc 13). Kept hand-synced with the
// PHP resources; enum-typed fields use the unions from ./enums.
import type {
    CheckInStatus,
    EnergyLevel,
    EventType,
    FlexibilityMode,
    FocusIntensity,
    MobilityPref,
    PlanStatus,
    SocialContext,
    TemplateScope,
} from './enums';

export type IntentDTO = {
    id: number;
    user_id: number | null;
    name: string;
    slug: string;
    color: string | null;
    icon: string | null;
    description?: string | null;
    is_system?: boolean;
};

export type ActivityDTO = {
    id: number;
    user_id: number | null;
    name: string;
    default_duration_minutes: number;
    tags: string[] | null;
    is_system?: boolean;
    intent_ids?: number[];
    intents?: IntentDTO[];
};

export type BlockDTO = {
    id: number;
    template_id: number;
    name: string;
    start_time: string;
    end_time: string;
    flexibility_mode: FlexibilityMode;
    category: string | null;
    priority: number;
    constraints: Record<string, unknown> | null;
    context: Record<string, unknown> | null;
    order: number;
    default_intents?: Array<IntentDTO & { weight: number }>;
};

export type TemplateAssignmentDTO = {
    id: number;
    template_id: number;
    scope: TemplateScope;
    days_of_week: number[] | null;
    starts_on: string | null;
    ends_on: string | null;
    priority: number;
};

export type TemplateDTO = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    forked_from_id: number | null;
    blocks_count?: number;
    blocks?: BlockDTO[];
    assignments?: TemplateAssignmentDTO[];
};

export type DailyPlanBlockDTO = {
    id: number;
    daily_plan_id: number;
    block_id: number | null;
    name: string;
    start_time: string;
    end_time: string;
    flexibility_mode: FlexibilityMode;
    intent_id: number | null;
    secondary_intent_id: number | null;
    energy_level: EnergyLevel | null;
    focus_intensity: FocusIntensity | null;
    social_context: SocialContext | null;
    mobility_preference: MobilityPref | null;
    constraints: Record<string, unknown> | null;
    context_tags: string[] | null;
    order: number;
    activities?: Array<
        ActivityDTO & { pivot: { order: number; estimated_minutes: number } }
    >;
    current_status?: CheckInStatus | null;
};

export type ScheduleEventDTO = {
    id: number;
    schedule_id: number;
    source_block_id: number | null;
    type: EventType;
    label: string;
    start_time: string;
    end_time: string;
    order: number;
    metadata: {
        start_min?: number;
        end_min?: number;
        intent_id?: number | null;
        energy?: EnergyLevel | null;
        restInserted?: boolean;
        overflow?: boolean;
        overran?: boolean;
        [key: string]: unknown;
    } | null;
};

export type ScheduleDTO = {
    id: number;
    daily_plan_id: number;
    version: number;
    generated_at: string;
    is_current: boolean;
    events: ScheduleEventDTO[];
};

export type DailyPlanDTO = {
    id: number;
    user_id: number;
    template_id: number | null;
    date: string;
    status: PlanStatus;
    blocks?: DailyPlanBlockDTO[];
    current_schedule?: ScheduleDTO | null;
};

export type PlanBlockComparison = {
    daily_plan_block_id: number;
    label: string;
    planned_start_min: number;
    planned_end_min: number;
    planned_minutes: number;
    actual_start_min: number | null;
    actual_end_min: number | null;
    actual_minutes: number | null;
    start_delta_min: number | null;
    duration_delta_min: number | null;
    status: CheckInStatus | null;
};
