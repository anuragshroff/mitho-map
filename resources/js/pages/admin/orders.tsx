import { Form, Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    assignDriver as adminOrdersAssignDriver,
    index as adminOrdersIndex,
    updateStatus as adminOrdersUpdateStatus,
} from '@/routes/admin/orders';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Orders',
        href: adminOrdersIndex().url,
    },
];

type DriverOption = {
    id: number;
    name: string;
};

type OrderRow = {
    id: number;
    status: string | null;
    customer_name: string | null;
    restaurant_name: string | null;
    driver_id: number | null;
    driver_name: string | null;
    total_cents: number;
    placed_at: string | null;
};

type PaginatedOrders = {
    data: OrderRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type OrdersFilters = {
    search: string;
    status: string;
};

type OrdersProps = {
    orders: PaginatedOrders;
    statusCounts: Record<string, number>;
    statusOptions: string[];
    drivers: DriverOption[];
    filters: OrdersFilters;
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

function toLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part[0]?.toUpperCase() + part.slice(1))
        .join(' ');
}

export default function AdminOrders({
    orders,
    statusCounts,
    statusOptions,
    drivers,
    filters,
}: OrdersProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin orders" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Order Queue</CardTitle>
                        <CardDescription>
                            {orders.total.toLocaleString()} orders total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminOrdersIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 md:grid-cols-[1fr_220px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by order, customer, restaurant"
                                defaultValue={filters.search}
                            />
                            <select
                                name="status"
                                defaultValue={filters.status}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">All statuses</option>
                                {statusOptions.map((status) => (
                                    <option key={status} value={status}>
                                        {toLabel(status)}
                                    </option>
                                ))}
                            </select>
                            <Button type="submit" variant="secondary">
                                Apply
                            </Button>
                            <Link
                                href={adminOrdersIndex()}
                                className="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm hover:bg-accent"
                                preserveScroll
                            >
                                Reset
                            </Link>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Manage Orders</CardTitle>
                        <CardDescription>
                            Update status and assign drivers from one place
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {orders.data.map((order) => (
                            <div
                                key={order.id}
                                className="space-y-3 rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            #{order.id} • {order.restaurant_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Customer: {order.customer_name ?? 'N/A'}
                                            {' • '}Driver:{' '}
                                            {order.driver_name ?? 'Unassigned'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Placed: {formatDate(order.placed_at)}
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

                                <div className="grid gap-2 lg:grid-cols-2">
                                    <Form
                                        action={
                                            adminOrdersUpdateStatus({
                                                order: order.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        className="flex flex-wrap gap-2"
                                    >
                                        <select
                                            name="status"
                                            defaultValue={order.status ?? ''}
                                            className="h-9 min-w-[180px] rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            {statusOptions.map((status) => (
                                                <option
                                                    key={`${order.id}-${status}`}
                                                    value={status}
                                                >
                                                    {toLabel(status)}
                                                </option>
                                            ))}
                                        </select>
                                        <Input
                                            name="notes"
                                            defaultValue="Updated from admin orders panel"
                                            className="min-w-[220px] flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                        >
                                            Update Status
                                        </Button>
                                    </Form>

                                    <Form
                                        action={
                                            adminOrdersAssignDriver({
                                                order: order.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        className="flex flex-wrap gap-2"
                                    >
                                        <select
                                            name="driver_id"
                                            defaultValue={
                                                order.driver_id?.toString() ?? ''
                                            }
                                            className="h-9 min-w-[180px] rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            <option value="">
                                                Unassign driver
                                            </option>
                                            {drivers.map((driver) => (
                                                <option
                                                    key={`${order.id}-${driver.id}`}
                                                    value={driver.id}
                                                >
                                                    {driver.name}
                                                </option>
                                            ))}
                                        </select>
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                        >
                                            Save Driver
                                        </Button>
                                    </Form>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Status Overview</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {Object.entries(statusCounts).map(([status, count]) => (
                            <Badge
                                key={status}
                                variant="outline"
                                className="px-3 py-1"
                            >
                                {toLabel(status)}: {count}
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {orders.from ?? 0} to {orders.to ?? 0} of{' '}
                        {orders.total.toLocaleString()} orders
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={orders.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                orders.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={orders.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                orders.next_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
