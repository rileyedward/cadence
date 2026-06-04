<script setup lang="ts">
import BottomNav from '@/components/AppShell/BottomNav.vue';
import OfflineBanner from '@/components/AppShell/OfflineBanner.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <OfflineBanner />

        <!-- Mobile gets extra bottom padding so content clears the fixed BottomNav. -->
        <div class="pb-safe-nav lg:pb-0">
            <slot />
        </div>

        <BottomNav />
    </AppLayout>
</template>
