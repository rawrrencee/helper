<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CalendarDays, DollarSign, FileText, Heart, LayoutGrid, Stethoscope, Users } from 'lucide-vue-next';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

const mainNavItems = computed<NavItem[]>(() => {
    if (user.value.role === 'admin') {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Helpers',
                href: '/helpers',
                icon: Users,
            },
            {
                title: 'Salary Payments',
                href: '/salary-payments',
                icon: DollarSign,
            },
            {
                title: 'Patients',
                href: '/patients',
                icon: Stethoscope,
            },
            {
                title: 'Family Info',
                href: '/family-info',
                icon: Heart,
            },
            {
                title: 'Appointments',
                href: '/appointments',
                icon: CalendarDays,
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'My Profile',
            href: '/helpers/' + (page.props.auth.user as any).helper_id,
            icon: Users,
        },
        {
            title: 'My Salary',
            href: '/salary-payments',
            icon: DollarSign,
        },
        {
            title: 'Patients',
            href: '/patients',
            icon: Stethoscope,
        },
        {
            title: 'Family Info',
            href: '/family-info',
            icon: Heart,
        },
        {
            title: 'Appointments',
            href: '/appointments',
            icon: CalendarDays,
        },
        {
            title: 'Documents',
            href: '/helpers/' + (page.props.auth.user as any).helper_id + '/documents',
            icon: FileText,
        },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
