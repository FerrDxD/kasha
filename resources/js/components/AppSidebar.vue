<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, CreditCard, ArrowLeftRight, PiggyBank, Target, TrendingUp, BarChart3, RefreshCw, SlidersHorizontal, FolderTree } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Transaksi', href: '/transactions', icon: ArrowLeftRight },
    { title: 'Akun', href: '/accounts', icon: CreditCard },
    { title: 'Kategori', href: '/categories', icon: FolderTree },
    { title: 'Budget', href: '/budgets', icon: PiggyBank },
    { title: 'Goals', href: '/goals', icon: Target },
    { title: 'Cash Flow', href: '/cash-flow', icon: TrendingUp },
    { title: 'Laporan', href: '/reports', icon: BarChart3 },
    { title: 'Recurring', href: '/recurring', icon: RefreshCw },
    { title: 'Rules', href: '/rules', icon: SlidersHorizontal },
];

const footerNavItems: NavItem[] = [];

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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
