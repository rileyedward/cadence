<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart3, CalendarDays, LayoutTemplate, Sun } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

// Mirrors the mobile BottomNav (doc 18): Today / Plan / Templates / Insights.
const mainNavItems: NavItem[] = [
    { title: 'Today', href: '/today', icon: Sun },
    { title: 'Plan', href: '/plans', icon: CalendarDays },
    { title: 'Templates', href: '/templates', icon: LayoutTemplate },
    { title: 'Insights', href: '/insights', icon: BarChart3 },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/today">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
