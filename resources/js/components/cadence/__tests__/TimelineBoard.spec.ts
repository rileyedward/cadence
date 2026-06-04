import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import TimelineBoard from '@/components/cadence/TimelineBoard.vue';
import type { ScheduleEventDTO } from '@/types/models';

// Stub the shared Inertia page props (intent library) used for colors.
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { intentLibrary: [{ id: 1, color: '#6366f1', name: 'Deep Work' }] } }),
}));

function event(partial: Partial<ScheduleEventDTO>): ScheduleEventDTO {
    return {
        id: 1,
        schedule_id: 1,
        source_block_id: 1,
        type: 'block',
        label: 'Work',
        start_time: '09:00',
        end_time: '10:00',
        order: 0,
        metadata: { start_min: 300, end_min: 360 },
        ...partial,
    };
}

describe('TimelineBoard', () => {
    it('renders block events and a buffer', () => {
        const events = [
            event({ id: 1, label: 'Morning', metadata: { start_min: 180, end_min: 240 } }),
            event({ id: 2, type: 'buffer', source_block_id: null, label: 'Buffer', metadata: { start_min: 240, end_min: 245 } }),
            event({ id: 3, label: 'Work', metadata: { start_min: 245, end_min: 600 } }),
        ];
        const wrapper = mount(TimelineBoard, { props: { events } });

        expect(wrapper.text()).toContain('Morning');
        expect(wrapper.text()).toContain('Work');
        // Two block buttons, one non-block label.
        expect(wrapper.findAll('button')).toHaveLength(2);
    });

    it('dims frozen events (ended before now)', () => {
        const events = [event({ id: 1, label: 'Past', metadata: { start_min: 180, end_min: 240 } })];
        const wrapper = mount(TimelineBoard, { props: { events, nowMin: 300 } });

        expect(wrapper.find('button').classes()).toContain('opacity-60');
    });

    it('emits select when a block is tapped', async () => {
        const events = [event({ id: 1, label: 'Work' })];
        const wrapper = mount(TimelineBoard, { props: { events } });

        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('select')).toBeTruthy();
    });
});
