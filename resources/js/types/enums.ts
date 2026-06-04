// Mirrors app/Enums/* PHP backed enums. Single source of values for both languages.
// A parity test (cadence:dump-enums) asserts these match the PHP cases.

export const FlexibilityMode = ['strict', 'soft', 'adaptive'] as const;
export type FlexibilityMode = (typeof FlexibilityMode)[number];

export const EnergyLevel = ['low', 'medium', 'high'] as const;
export type EnergyLevel = (typeof EnergyLevel)[number];

export const FocusIntensity = ['light', 'normal', 'deep'] as const;
export type FocusIntensity = (typeof FocusIntensity)[number];

export const SocialContext = ['solo', 'social', 'mixed'] as const;
export type SocialContext = (typeof SocialContext)[number];

export const MobilityPref = ['stationary', 'mobile', 'mixed'] as const;
export type MobilityPref = (typeof MobilityPref)[number];

export const TemplateScope = ['weekday', 'weekend', 'custom'] as const;
export type TemplateScope = (typeof TemplateScope)[number];

export const PlanStatus = ['draft', 'generated', 'active', 'done'] as const;
export type PlanStatus = (typeof PlanStatus)[number];

export const EventType = ['block', 'buffer', 'transition'] as const;
export type EventType = (typeof EventType)[number];

export const CheckInStatus = ['started', 'completed', 'skipped'] as const;
export type CheckInStatus = (typeof CheckInStatus)[number];

// Used by the enum-parity test to compare against `php artisan cadence:dump-enums`.
export const ENUM_REGISTRY: Record<string, readonly string[]> = {
    FlexibilityMode,
    EnergyLevel,
    FocusIntensity,
    SocialContext,
    MobilityPref,
    TemplateScope,
    PlanStatus,
    EventType,
    CheckInStatus,
};
