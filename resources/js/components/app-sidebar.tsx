import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    Calculator,
    CircleUserRound,
    ClipboardList,
    Folder,
    Image,
    LayoutGrid,
    Percent,
    ShoppingBag,
    Store,
    Tag,
    Tv,
    UtensilsCrossed,
} from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
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
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminBannersIndex } from '@/routes/admin/banners';
import { index as adminCouponsIndex } from '@/routes/admin/coupons';
import { index as adminKitchenOrderTicketsIndex } from '@/routes/admin/kitchen-order-tickets';
import { index as adminMenuItemsIndex } from '@/routes/admin/menu-items';
import { index as adminOrdersIndex } from '@/routes/admin/orders';
import { index as adminRestaurantsIndex } from '@/routes/admin/restaurants';
import { index as adminSpecialOffersIndex } from '@/routes/admin/special-offers';
import { index as adminStoriesIndex } from '@/routes/admin/stories';
import { index as adminUsersIndex } from '@/routes/admin/users';
import type { NavItem, SharedData } from '@/types';
import { NavUser } from './nav-user';
import AppLogo from './app-logo';

const baseNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Overview',
        href: adminDashboard(),
        icon: Building2,
    },
    {
        title: 'Orders',
        href: adminOrdersIndex(),
        icon: ShoppingBag,
    },
    {
        title: 'Kitchen Tickets',
        href: adminKitchenOrderTicketsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Menu Items',
        href: adminMenuItemsIndex(),
        icon: UtensilsCrossed,
    },
    {
        title: 'Restaurants',
        href: adminRestaurantsIndex(),
        icon: Store,
    },
    {
        title: 'Stories',
        href: adminStoriesIndex(),
        icon: Tv,
    },
    {
        title: 'Banners',
        href: adminBannersIndex(),
        icon: Image,
    },
    {
        title: 'Special Offers',
        href: adminSpecialOffersIndex(),
        icon: Tag,
    },
    {
        title: 'Coupons',
        href: adminCouponsIndex(),
        icon: Percent,
    },
    {
        title: 'Users',
        href: adminUsersIndex(),
        icon: CircleUserRound,
    },
    {
        title: 'POS',
        href: '/admin/pos',
        icon: Calculator,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const isAdmin = auth.user.role === 'admin';
    const mainNavItems = isAdmin
        ? [...baseNavItems, ...adminNavItems]
        : baseNavItems;

    const homeRoute = isAdmin ? adminDashboard() : dashboard();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeRoute} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
