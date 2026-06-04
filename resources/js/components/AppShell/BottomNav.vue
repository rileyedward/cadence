<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, CalendarDays, LayoutTemplate, Sun } from '@lucide/vue';
import { computed } from 'vue';

type Dest = {
    title: string;
    href: string;
    icon: typeof Sun;
};

// Primary destinations (doc 18): Today / Plan / Templates / Insights.
const destinations: Dest[] = [
    { title: 'Today', href: '/today', icon: Sun },
    { title: 'Plan', href: '/plans', icon: CalendarDays },
    { title: 'Templates', href: '/templates', icon: LayoutTemplate },
    { title: 'Insights', href: '/insights', icon: BarChart3 },
];

const page = usePage();
// page.url is the current path + query (e.g. "/today").
const currentPath = computed(() => page.url.split('?')[0]);

function isActive(href: string): boolean {
    return (
        currentPath.value === href || currentPath.value.startsWith(href + '/')
    );
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card/95 safe-bottom backdrop-blur lg:hidden"
        aria-label="Primary"
    >
        <ul class="mx-auto flex max-w-md items-stretch justify-around">
            <li v-for="dest in destinations" :key="dest.href" class="flex-1">
                <Link
                    :href="dest.href"
                    class="flex h-16 min-h-[44px] flex-col items-center justify-center gap-1 text-xs font-medium transition-colors"
                    :class="
                        isActive(dest.href)
                            ? 'text-primary'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-current="isActive(dest.href) ? 'page' : undefined"
                >
                    <component :is="dest.icon" class="size-5" />
                    <span>{{ dest.title }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
