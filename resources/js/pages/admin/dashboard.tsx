import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminCouponsIndex } from '@/routes/admin/coupons';
import { index as adminKitchenOrderTicketsIndex } from '@/routes/admin/kitchen-order-tickets';
import { index as adminMenuItemsIndex } from '@/routes/admin/menu-items';
import { index as adminOrdersIndex } from '@/routes/admin/orders';
import { index as adminRestaurantsIndex } from '@/routes/admin/restaurants';
import { index as adminStoriesIndex } from '@/routes/admin/stories';
import { index as adminUsersIndex } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
];

type Summary = {
    total_orders: number;
    active_orders: number;
    delivered_today: number;
    today_revenue_cents: number;
    open_restaurants: number;
    total_menu_items: number;
    active_stories: number;
    active_coupons: number;
    total_users: number;
    online_drivers: number;
    open_kitchen_tickets: number;
};

type RecentOrder = {
    id: number;
    status: string | null;
    total_cents: number;
    placed_at: string | null;
    customer_name: string | null;
    restaurant_name: string | null;
};

type DashboardProps = {
    summary: Summary;
    recentOrders: RecentOrder[];
    orderStatusCounts: Record<string, number>;
};

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

function formatMoney(cents: number): string {
    return currency.format(cents / 100);
}

function formatDate(iso: string | null): string {
    if (iso === null) {
        return 'Not available';
    }

    return new Date(iso).toLocaleString();
}

function statusVariant(status: string | null): 'default' | 'secondary' | 'outline' {
    if (status === null) {
        return 'secondary';
    }

    if (status === 'delivered') {
        return 'default';
    }

    if (status === 'cancelled') {
        return 'outline';
    }

    return 'secondary';
}

export default function AdminDashboard({
    summary,
    recentOrders,
    orderStatusCounts,
}: DashboardProps) {
    const cards = [
        {
            title: 'Total Orders',
            value: summary.total_orders.toLocaleString(),
            description: 'All-time orders across restaurants',
        },
        {
            title: 'Active Orders',
            value: summary.active_orders.toLocaleString(),
            description: 'Orders currently in progress',
        },
        {
            title: 'Delivered Today',
            value: summary.delivered_today.toLocaleString(),
            description: 'Successfully completed today',
        },
        {
            title: 'Today Revenue',
            value: formatMoney(summary.today_revenue_cents),
            description: 'Gross order value for today',
        },
        {
            title: 'Open Restaurants',
            value: summary.open_restaurants.toLocaleString(),
            description: 'Restaurants currently accepting orders',
        },
        {
            title: 'Online Drivers',
            value: summary.online_drivers.toLocaleString(),
            description: 'Drivers seen in last 15 minutes',
        },
        {
            title: 'Open KOT Tickets',
            value: summary.open_kitchen_tickets.toLocaleString(),
            description: 'Kitchen tickets not yet completed',
        },
        {
            title: 'Menu Items',
            value: summary.total_menu_items.toLocaleString(),
            description: 'Total catalog items across restaurants',
        },
        {
            title: 'Active Coupons',
            value: summary.active_coupons.toLocaleString(),
            description: 'Coupons currently available for redemption',
        },
        {
            title: 'Total Users',
            value: summary.total_users.toLocaleString(),
            description: 'Customers, restaurants, drivers, and admins',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin overview" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => (
                        <Card key={card.title}>
                            <CardHeader className="gap-2">
                                <CardDescription>{card.title}</CardDescription>
                                <CardTitle className="text-3xl tracking-tight">
                                    {card.value}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-xs text-muted-foreground">
                                {card.description}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-[2fr_1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Orders</CardTitle>
                            <CardDescription>
                                Latest customer activity across the platform
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {recentOrders.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No orders found yet.
                                </p>
                            )}

                            {recentOrders.map((order) => (
                                <div
                                    key={order.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            #{order.id} {order.restaurant_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {order.customer_name} •{' '}
                                            {formatDate(order.placed_at)}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Badge variant={statusVariant(order.status)}>
                                            {order.status?.replaceAll('_', ' ') ??
                                                'unknown'}
                                        </Badge>
                                        <p className="text-sm font-semibold">
                                            {formatMoney(order.total_cents)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Links</CardTitle>
                            <CardDescription>
                                Jump to admin management sections
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Link
                                href={adminOrdersIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Orders
                            </Link>
                            <Link
                                href={adminRestaurantsIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Restaurants
                            </Link>
                            <Link
                                href={adminStoriesIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Review Stories
                            </Link>
                            <Link
                                href={adminKitchenOrderTicketsIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Kitchen Tickets
                            </Link>
                            <Link
                                href={adminMenuItemsIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Menu Items
                            </Link>
                            <Link
                                href={adminUsersIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Users
                            </Link>
                            <Link
                                href={adminCouponsIndex()}
                                className="block rounded-lg border px-3 py-2 text-sm font-medium hover:bg-accent"
                                prefetch
                            >
                                Manage Coupons
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Status Distribution</CardTitle>
                        <CardDescription>
                            Order volume by lifecycle status
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {Object.entries(orderStatusCounts).map(
                            ([status, count]) => (
                                <Badge
                                    key={status}
                                    variant="outline"
                                    className="px-3 py-1"
                                >
                                    {status.replaceAll('_', ' ')}: {count}
                                </Badge>
                            ),
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
