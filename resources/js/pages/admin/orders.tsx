import { Form, Head, Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
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
import { cn } from '@/lib/utils';
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

type ConversationType = 'user_admin' | 'admin_driver';

type DriverOption = {
    id: number;
    name: string;
    active_assignments_count: number;
    is_available: boolean;
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

type ChatMessage = {
    id: number;
    order_id: number;
    conversation_type: string | null;
    message: string;
    sent_at: string | null;
    sender: {
        id: number | null;
        name: string | null;
        role: string | null;
    };
};

type EchoChatEventPayload = {
    message?: ChatMessage;
};

type EchoPrivateChannelLike = {
    listen(event: string, callback: (payload: EchoChatEventPayload) => void): void;
};

type EchoLike = {
    private(channelName: string): EchoPrivateChannelLike;
    leave(channelName: string): void;
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
    driverAssignmentLimit: number;
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

function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const metaTag = document.querySelector('meta[name="csrf-token"]');

    return metaTag instanceof HTMLMetaElement ? metaTag.content : '';
}

function buildChatKey(orderId: number, conversationType: ConversationType): string {
    return `${orderId}:${conversationType}`;
}

function getEchoClient(): EchoLike | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const maybeEcho = (window as Window & { Echo?: EchoLike }).Echo;

    return maybeEcho ?? null;
}

export default function AdminOrders({
    orders,
    statusCounts,
    statusOptions,
    drivers,
    driverAssignmentLimit,
    filters,
}: OrdersProps) {
    const [activeChatOrderId, setActiveChatOrderId] = useState<number | null>(null);
    const [conversationByOrder, setConversationByOrder] = useState<Record<number, ConversationType>>({});
    const [messagesByKey, setMessagesByKey] = useState<Record<string, ChatMessage[]>>({});
    const [loadingByKey, setLoadingByKey] = useState<Record<string, boolean>>({});
    const [draftByKey, setDraftByKey] = useState<Record<string, string>>({});
    const [errorByKey, setErrorByKey] = useState<Record<string, string | null>>({});

    const activeConversationType = useMemo<ConversationType>(() => {
        if (activeChatOrderId === null) {
            return 'user_admin';
        }

        return conversationByOrder[activeChatOrderId] ?? 'user_admin';
    }, [activeChatOrderId, conversationByOrder]);

    const loadMessages = useCallback(
        async (orderId: number, conversationType: ConversationType): Promise<void> => {
            const key = buildChatKey(orderId, conversationType);

            setLoadingByKey((current) => ({
                ...current,
                [key]: true,
            }));

            setErrorByKey((current) => ({
                ...current,
                [key]: null,
            }));

            try {
                const response = await fetch(
                    `/api/v1/orders/${orderId}/chat/messages?conversation_type=${conversationType}`,
                    {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                );

                if (!response.ok) {
                    const fallbackMessage = `Unable to load chat (${response.status}).`;

                    setErrorByKey((current) => ({
                        ...current,
                        [key]: fallbackMessage,
                    }));

                    return;
                }

                const payload: { messages?: ChatMessage[] } = await response.json();

                setMessagesByKey((current) => ({
                    ...current,
                    [key]: payload.messages ?? [],
                }));
            } finally {
                setLoadingByKey((current) => ({
                    ...current,
                    [key]: false,
                }));
            }
        },
        [],
    );

    useEffect(() => {
        if (activeChatOrderId === null) {
            return;
        }

        void loadMessages(activeChatOrderId, activeConversationType);

        const channelName = `orders.${activeChatOrderId}.conversation.${activeConversationType}`;
        const echo = getEchoClient();

        if (echo !== null) {
            const channel = echo.private(channelName);

            channel.listen('.order.chat.message.sent', (payload) => {
                const incomingMessage = payload.message;

                if (incomingMessage === undefined) {
                    void loadMessages(activeChatOrderId, activeConversationType);

                    return;
                }

                const key = buildChatKey(activeChatOrderId, activeConversationType);

                setMessagesByKey((current) => {
                    const currentMessages = current[key] ?? [];
                    const alreadyIncluded = currentMessages.some(
                        (message) => message.id === incomingMessage.id,
                    );

                    if (alreadyIncluded) {
                        return current;
                    }

                    return {
                        ...current,
                        [key]: [...currentMessages, incomingMessage],
                    };
                });
            });

            return () => {
                echo.leave(channelName);
            };
        }

        const intervalId = window.setInterval(() => {
            void loadMessages(activeChatOrderId, activeConversationType);
        }, 7000);

        return () => {
            window.clearInterval(intervalId);
        };
    }, [activeChatOrderId, activeConversationType, loadMessages]);

    const handleOpenChat = (
        orderId: number,
        hasAssignedDriver: boolean,
    ): void => {
        setActiveChatOrderId(orderId);

        setConversationByOrder((current) => {
            const currentValue = current[orderId];

            if (currentValue === 'admin_driver' && !hasAssignedDriver) {
                return {
                    ...current,
                    [orderId]: 'user_admin',
                };
            }

            return {
                ...current,
                [orderId]: currentValue ?? 'user_admin',
            };
        });
    };

    const handleConversationChange = (orderId: number, conversationType: ConversationType): void => {
        setConversationByOrder((current) => ({
            ...current,
            [orderId]: conversationType,
        }));

        void loadMessages(orderId, conversationType);
    };

    const handleDraftChange = (key: string, value: string): void => {
        setDraftByKey((current) => ({
            ...current,
            [key]: value,
        }));
    };

    const handleSendMessage = async (orderId: number, conversationType: ConversationType): Promise<void> => {
        const key = buildChatKey(orderId, conversationType);
        const draft = (draftByKey[key] ?? '').trim();

        if (draft === '') {
            setErrorByKey((current) => ({
                ...current,
                [key]: 'Message cannot be empty.',
            }));

            return;
        }

        setLoadingByKey((current) => ({
            ...current,
            [key]: true,
        }));

        setErrorByKey((current) => ({
            ...current,
            [key]: null,
        }));

        try {
            const response = await fetch(`/api/v1/orders/${orderId}/chat/messages`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    conversation_type: conversationType,
                    message: draft,
                }),
            });

            if (!response.ok) {
                const payload: { message?: string; errors?: Record<string, string[]> } = await response.json();
                const firstError = payload.errors === undefined
                    ? undefined
                    : Object.values(payload.errors)[0]?.[0];

                setErrorByKey((current) => ({
                    ...current,
                    [key]: firstError ?? payload.message ?? `Unable to send message (${response.status}).`,
                }));

                return;
            }

            setDraftByKey((current) => ({
                ...current,
                [key]: '',
            }));

            await loadMessages(orderId, conversationType);
        } finally {
            setLoadingByKey((current) => ({
                ...current,
                [key]: false,
            }));
        }
    };

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
                            Update status, assign drivers, and manage support chat
                        </CardDescription>
                        <CardDescription>
                            Driver max active assignments: {driverAssignmentLimit}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {orders.data.map((order) => {
                            const currentConversationType =
                                conversationByOrder[order.id] ?? 'user_admin';
                            const hasAssignedDriver = order.driver_id !== null;
                            const chatKey = buildChatKey(order.id, currentConversationType);
                            const chatMessages = messagesByKey[chatKey] ?? [];
                            const isChatOpen = activeChatOrderId === order.id;
                            const isChatLoading = loadingByKey[chatKey] ?? false;
                            const chatError = errorByKey[chatKey];
                            const chatDraft = draftByKey[chatKey] ?? '';

                            return (
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
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    handleOpenChat(
                                                        order.id,
                                                        hasAssignedDriver,
                                                    );
                                                }}
                                            >
                                                {isChatOpen ? 'Chat Open' : 'Open Chat'}
                                            </Button>
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
                                                        disabled={
                                                            !driver.is_available &&
                                                            driver.id !==
                                                                order.driver_id
                                                        }
                                                    >
                                                        {driver.name}
                                                        {' • '}
                                                        Active:{' '}
                                                        {
                                                            driver.active_assignments_count
                                                        }
                                                        {!driver.is_available &&
                                                        driver.id !==
                                                            order.driver_id
                                                            ? ' (Busy)'
                                                            : ''}
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

                                    {isChatOpen && (
                                        <div className="space-y-3 rounded-md border bg-muted/20 p-3">
                                            <div className="flex flex-wrap gap-2">
                                                {([
                                                    {
                                                        value: 'user_admin' as const,
                                                        label: 'User ↔ Admin',
                                                    },
                                                    {
                                                        value: 'admin_driver' as const,
                                                        label: 'Admin ↔ Driver',
                                                    },
                                                ]).map((thread) => (
                                                    <Button
                                                        key={`${order.id}-${thread.value}`}
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className={cn(
                                                            currentConversationType ===
                                                                thread.value &&
                                                                'bg-accent',
                                                        )}
                                                        onClick={() => {
                                                            handleConversationChange(
                                                                order.id,
                                                                thread.value,
                                                            );
                                                        }}
                                                        disabled={
                                                            thread.value ===
                                                                'admin_driver' &&
                                                            !hasAssignedDriver
                                                        }
                                                    >
                                                        {thread.label}
                                                    </Button>
                                                ))}
                                            </div>

                                            {!hasAssignedDriver && (
                                                <p className="text-xs text-muted-foreground">
                                                    Assign a driver to enable
                                                    Admin ↔ Driver chat.
                                                </p>
                                            )}

                                            {chatError !== null &&
                                                chatError !== undefined &&
                                                chatError !== '' && (
                                                    <p className="text-xs text-destructive">
                                                        {chatError}
                                                    </p>
                                                )}

                                            <div className="max-h-56 space-y-2 overflow-y-auto rounded-md border bg-background p-2">
                                                {chatMessages.length === 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {isChatLoading
                                                            ? 'Loading messages...'
                                                            : 'No messages yet.'}
                                                    </p>
                                                )}

                                                {chatMessages.map((message) => (
                                                    <div
                                                        key={message.id}
                                                        className="rounded-md border p-2"
                                                    >
                                                        <div className="flex items-center justify-between gap-2">
                                                            <p className="text-xs font-medium">
                                                                {message.sender
                                                                    .name ??
                                                                    'Unknown'}
                                                                {' • '}
                                                                {message.sender
                                                                    .role ??
                                                                    'unknown'}
                                                            </p>
                                                            <p className="text-[11px] text-muted-foreground">
                                                                {formatDate(
                                                                    message.sent_at,
                                                                )}
                                                            </p>
                                                        </div>
                                                        <p className="mt-1 text-sm">
                                                            {message.message}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                <Input
                                                    value={chatDraft}
                                                    onChange={(event) => {
                                                        handleDraftChange(
                                                            chatKey,
                                                            event.target.value,
                                                        );
                                                    }}
                                                    placeholder="Type a message..."
                                                    className="min-w-[220px] flex-1"
                                                    onKeyDown={(event) => {
                                                        if (
                                                            event.key ===
                                                                'Enter' &&
                                                            !event.shiftKey
                                                        ) {
                                                            event.preventDefault();
                                                            void handleSendMessage(
                                                                order.id,
                                                                currentConversationType,
                                                            );
                                                        }
                                                    }}
                                                />
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    onClick={() => {
                                                        void handleSendMessage(
                                                            order.id,
                                                            currentConversationType,
                                                        );
                                                    }}
                                                    disabled={isChatLoading}
                                                >
                                                    Send
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
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
