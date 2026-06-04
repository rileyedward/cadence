import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmptyState from '@/components/cadence/EmptyState.vue';

describe('EmptyState', () => {
    it('renders title and description', () => {
        const wrapper = mount(EmptyState, {
            props: {
                title: 'No templates yet',
                description: 'Create one to begin.',
            },
        });

        expect(wrapper.text()).toContain('No templates yet');
        expect(wrapper.text()).toContain('Create one to begin.');
    });

    it('renders default slot content when provided', () => {
        const wrapper = mount(EmptyState, {
            props: { title: 'Empty' },
            slots: { default: '<button>Do thing</button>' },
        });

        expect(wrapper.find('button').exists()).toBe(true);
    });
});
