<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/profile';

defineProps<{
    configured: boolean;
    connected: boolean;
    calendarId: string | null;
    pushBuffers: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Calendar', href: '/settings/calendar' }],
    },
});

function disconnect() {
    router.delete('/calendar/disconnect', { preserveScroll: true });
}

function importToday() {
    router.post('/calendar/import', { date: format(new Date(), 'yyyy-MM-dd') }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Calendar" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Google Calendar"
            description="Sync your generated day to Google Calendar and import busy events as fixed anchors."
        />

        <div v-if="!configured" class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground">
            Google Calendar isn't configured on this server. Add
            <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>, and
            <code>GOOGLE_REDIRECT_URI</code> to enable it. Cadence works fully without it.
        </div>

        <template v-else>
            <div class="rounded-lg border border-border p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium">
                            {{ connected ? 'Connected' : 'Not connected' }}
                        </p>
                        <p v-if="connected && calendarId" class="text-sm text-muted-foreground">
                            Calendar: {{ calendarId }}
                        </p>
                    </div>
                    <a v-if="!connected" href="/calendar/connect">
                        <Button>Connect Google</Button>
                    </a>
                    <Button v-else variant="outline" @click="disconnect">Disconnect</Button>
                </div>
            </div>

            <div v-if="connected" class="rounded-lg border border-border p-4">
                <p class="mb-1 font-medium">Import busy events</p>
                <p class="mb-3 text-sm text-muted-foreground">
                    Pull today's calendar events so the compiler reflows around them as fixed anchors.
                    Buffers are {{ pushBuffers ? 'included in' : 'excluded from' }} pushes.
                </p>
                <Button variant="outline" @click="importToday">Import today's busy events</Button>
            </div>
        </template>

        <p>
            <Link :href="edit()" class="text-sm text-muted-foreground hover:text-foreground">
                ← Back to profile
            </Link>
        </p>
    </div>
</template>
