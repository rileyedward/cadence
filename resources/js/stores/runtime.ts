import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { ref } from 'vue';

/**
 * Runtime op state (doc 08). The server is authoritative: we post an op and let
 * the fresh schedule come back via Inertia props. (The cosmetic optimistic drag
 * transform is added with the touch timeline in Phase 6.)
 */
export const useRuntimeStore = defineStore('runtime', () => {
    const inFlight = ref(false);

    function runOp(
        planId: number,
        op: string,
        payload: Record<string, string | number | boolean | null> = {},
    ) {
        inFlight.value = true;
        router.post(`/plans/${planId}/runtime/${op}`, payload, {
            preserveScroll: true,
            onFinish: () => (inFlight.value = false),
        });
    }

    return { inFlight, runOp };
});
