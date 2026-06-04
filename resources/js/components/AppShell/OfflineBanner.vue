<script setup lang="ts">
import { WifiOff } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';

// Clear offline state for the installable PWA (doc 18). Offline *data*/sync is
// deferred — for now reads/writes need connectivity, so we surface it plainly.
const online = ref(true);

function update() {
    online.value = navigator.onLine;
}

onMounted(() => {
    update();
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
});

onUnmounted(() => {
    window.removeEventListener('online', update);
    window.removeEventListener('offline', update);
});
</script>

<template>
    <Transition name="fade">
        <div
            v-if="!online"
            class="fixed inset-x-0 top-0 z-50 flex items-center justify-center gap-2 bg-amber-500 px-4 py-1.5 text-sm font-medium text-amber-950 safe-top"
            role="status"
        >
            <WifiOff class="size-4" />
            You're offline — changes are paused until you reconnect.
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
