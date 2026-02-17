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
    index as adminKitchenOrderTicketsIndex,
    updateStatus as adminKitchenOrderTicketsUpdateStatus,
} from '@/routes/admin/kitchen-order-tickets';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Kitchen Tickets',
        href: adminKitchenOrderTicketsIndex().url,
    },
];

type KitchenOrderTicketRow = {
    id: number;
    order_id: number;
    status: string | null;
    notes: string | null;
    accepted_at: string | null;
    ready_at: string | null;
    restaurant_name: string | null;
    customer_name: string | null;
    order_status: string | null;
    order_total_cents: number | null;
};

type PaginatedKitchenOrderTickets = {
    data: KitchenOrderTicketRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type KitchenOrderTicketFilters = {
    search: string;
    status: string;
};

type KitchenOrderTicketsProps = {
    kitchenOrderTickets: PaginatedKitchenOrderTickets;
    statusCounts: Record<string, number>;
    statusOptions: string[];
    filters: KitchenOrderTicketFilters;
};

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

function toLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part[0]?.toUpperCase() + part.slice(1))
        .join(' ');
}

function formatMoney(cents: number | null): string {
    if (cents === null) {
        return 'N/A';
    }

    return currency.format(cents / 100);
}

function formatDate(iso: string | null): string {
    if (iso === null) {
        return 'Not available';
    }

    return new Date(iso).toLocaleString();
}

function ticketVariant(status: string | null): 'default' | 'secondary' | 'outline' {
    if (status === null) {
        return 'secondary';
    }

    if (status === 'completed') {
        return 'default';
    }

    if (status === 'cancelled') {
        return 'outline';
    }

    return 'secondary';
}

export default function AdminKitchenOrderTickets({
    kitchenOrderTickets,
    statusCounts,
    statusOptions,
    filters,
}: KitchenOrderTicketsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin kitchen tickets" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Kitchen Tickets</CardTitle>
                        <CardDescription>
                            {kitchenOrderTickets.total.toLocaleString()} tickets in total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminKitchenOrderTicketsIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 md:grid-cols-[1fr_220px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by ticket, order, restaurant, customer"
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
                                href={adminKitchenOrderTicketsIndex()}
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
                        <CardTitle>Manage Kitchen Flow</CardTitle>
                        <CardDescription>
                            Update kitchen status and keep order lifecycle in sync
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {kitchenOrderTickets.data.map((ticket) => (
                            <div
                                key={ticket.id}
                                className="space-y-3 rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            Ticket #{ticket.id} • Order #{ticket.order_id}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {ticket.restaurant_name} {' • '}Customer:{' '}
                                            {ticket.customer_name ?? 'N/A'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Accepted: {formatDate(ticket.accepted_at)} {' • '}
                                            Ready: {formatDate(ticket.ready_at)}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={ticketVariant(ticket.status)}>
                                            {toLabel(ticket.status ?? 'unknown')}
                                        </Badge>
                                        <Badge variant="outline">
                                            Order: {toLabel(ticket.order_status ?? 'unknown')}
                                        </Badge>
                                        <p className="text-sm font-semibold">
                                            {formatMoney(ticket.order_total_cents)}
                                        </p>
                                    </div>
                                </div>

                                <Form
                                    action={
                                        adminKitchenOrderTicketsUpdateStatus({
                                            kitchenOrderTicket: ticket.id,
                                        }).url
                                    }
                                    method="patch"
                                    options={{ preserveScroll: true }}
                                    className="flex flex-wrap gap-2"
                                >
                                    <select
                                        name="status"
                                        defaultValue={ticket.status ?? ''}
                                        className="h-9 min-w-[180px] rounded-md border bg-transparent px-3 text-sm"
                                    >
                                        {statusOptions.map((status) => (
                                            <option
                                                key={`${ticket.id}-${status}`}
                                                value={status}
                                            >
                                                {toLabel(status)}
                                            </option>
                                        ))}
                                    </select>
                                    <Input
                                        name="notes"
                                        defaultValue={ticket.notes ?? ''}
                                        placeholder="Notes for kitchen / ops"
                                        className="min-w-[220px] flex-1"
                                    />
                                    <Button type="submit" size="sm" variant="outline">
                                        Update Ticket
                                    </Button>
                                </Form>
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
                            <Badge key={status} variant="outline" className="px-3 py-1">
                                {toLabel(status)}: {count}
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {kitchenOrderTickets.from ?? 0} to {kitchenOrderTickets.to ?? 0}{' '}
                        of {kitchenOrderTickets.total.toLocaleString()} tickets
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={kitchenOrderTickets.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                kitchenOrderTickets.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={kitchenOrderTickets.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                kitchenOrderTickets.next_page_url === null
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
