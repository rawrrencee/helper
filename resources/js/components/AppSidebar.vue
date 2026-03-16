<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CalendarDays, CalendarRange, ClipboardList, DollarSign, FileText, Heart, LayoutGrid, Stethoscope, Users } from 'lucide-vue-next';
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
import type { NavGroup } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navGroups = computed<NavGroup[]>(() => {
    if (user.value.role === 'admin') {
        return [
            {
                label: 'Overview',
                items: [
                    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
                ],
            },
            {
                label: 'People',
                items: [
                    { title: 'Helpers', href: '/helpers', icon: Users },
                    { title: 'Patients', href: '/patients', icon: Stethoscope },
                    { title: 'Family Info', href: '/family-info', icon: Heart },
                ],
            },
            {
                label: 'Finance',
                items: [
                    { title: 'Salary Payments', href: '/salary-payments', icon: DollarSign },
                    { title: 'Claims', href: '/claims', icon: ClipboardList },
                ],
            },
            {
                label: 'Schedule',
                items: [
                    { title: 'Calendars', href: '/calendars', icon: CalendarRange },
                    { title: 'Appointments', href: '/appointments', icon: CalendarDays },
                ],
            },
        ];
    }

    const helperId = (page.props.auth.user as any).helper_id;

    return [
        {
            label: 'Overview',
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            ],
        },
        {
            label: 'My Info',
            items: [
                { title: 'My Profile', href: '/helpers/' + helperId, icon: Users },
                { title: 'Documents', href: '/helpers/' + helperId + '/documents', icon: FileText },
            ],
        },
        {
            label: 'Finance',
            items: [
                { title: 'My Salary', href: '/salary-payments', icon: DollarSign },
                { title: 'My Claims', href: '/claims', icon: ClipboardList },
            ],
        },
        {
            label: 'Care',
            items: [
                { title: 'Patients', href: '/patients', icon: Stethoscope },
                { title: 'Calendars', href: '/calendars', icon: CalendarRange },
                { title: 'Appointments', href: '/appointments', icon: CalendarDays },
                { title: 'Family Info', href: '/family-info', icon: Heart },
            ],
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
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
