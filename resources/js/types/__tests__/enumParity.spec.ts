import { describe, expect, it } from 'vitest';
import { ENUM_REGISTRY } from '@/types/enums';

/**
 * Enum value parity (doc 17). The canonical values here mirror app/Enums/*; the
 * `cadence:dump-enums` artisan command emits the same map from the PHP side, and
 * a Pest test (tests/Unit/EnumValuesTest.php) asserts the PHP enums match it too.
 * Keeping both sides pinned to this map means the TS unions can't silently drift.
 */
const CANONICAL: Record<string, string[]> = {
    FlexibilityMode: ['strict', 'soft', 'adaptive'],
    EnergyLevel: ['low', 'medium', 'high'],
    FocusIntensity: ['light', 'normal', 'deep'],
    SocialContext: ['solo', 'social', 'mixed'],
    MobilityPref: ['stationary', 'mobile', 'mixed'],
    TemplateScope: ['weekday', 'weekend', 'custom'],
    PlanStatus: ['draft', 'generated', 'active', 'done'],
    EventType: ['block', 'buffer', 'transition'],
    CheckInStatus: ['started', 'completed', 'skipped'],
};

describe('enum parity', () => {
    it('registers every canonical enum', () => {
        expect(Object.keys(ENUM_REGISTRY).sort()).toEqual(
            Object.keys(CANONICAL).sort(),
        );
    });

    it.each(Object.entries(CANONICAL))(
        '%s has the expected values',
        (name, expected) => {
            expect([...ENUM_REGISTRY[name]]).toEqual(expected);
        },
    );
});
