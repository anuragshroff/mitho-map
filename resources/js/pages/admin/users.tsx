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
import { index as adminUsersIndex, updateRole as adminUsersUpdateRole } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Users',
        href: adminUsersIndex().url,
    },
];

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    email_verified_at: string | null;
    created_at: string | null;
};

type PaginatedUsers = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type UserFilters = {
    search: string;
    role: string;
};

type UsersProps = {
    users: PaginatedUsers;
    roleCounts: Record<string, number>;
    roleOptions: string[];
    currentAdminId: number | null;
    filters: UserFilters;
};

function toLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part[0]?.toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDate(iso: string | null): string {
    if (iso === null) {
        return 'Not available';
    }

    return new Date(iso).toLocaleString();
}

export default function AdminUsers({
    users,
    roleCounts,
    roleOptions,
    currentAdminId,
    filters,
}: UsersProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin users" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                        <CardDescription>
                            {users.total.toLocaleString()} users in the platform
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminUsersIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 md:grid-cols-[1fr_220px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by name or email"
                                defaultValue={filters.search}
                            />
                            <select
                                name="role"
                                defaultValue={filters.role}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">All roles</option>
                                {roleOptions.map((role) => (
                                    <option key={role} value={role}>
                                        {toLabel(role)}
                                    </option>
                                ))}
                            </select>
                            <Button type="submit" variant="secondary">
                                Apply
                            </Button>
                            <Link
                                href={adminUsersIndex()}
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
                        <CardTitle>Manage User Roles</CardTitle>
                        <CardDescription>
                            Assign customer, restaurant, driver, or admin access
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {users.data.map((user) => (
                            <div
                                key={user.id}
                                className="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_auto] md:items-center"
                            >
                                <div>
                                    <p className="text-sm font-medium">{user.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {user.email}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Joined {formatDate(user.created_at)} {' • '}
                                        {user.email_verified_at === null
                                            ? 'Email unverified'
                                            : 'Email verified'}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        {toLabel(user.role ?? 'unknown')}
                                    </Badge>

                                    {user.id === currentAdminId ? (
                                        <Badge variant="secondary">
                                            Current Admin
                                        </Badge>
                                    ) : (
                                        <Form
                                            action={
                                                adminUsersUpdateRole({
                                                    user: user.id,
                                                }).url
                                            }
                                            method="patch"
                                            options={{ preserveScroll: true }}
                                            className="flex items-center gap-2"
                                        >
                                            <select
                                                name="role"
                                                defaultValue={user.role ?? ''}
                                                className="h-9 min-w-[170px] rounded-md border bg-transparent px-3 text-sm"
                                            >
                                                {roleOptions.map((role) => (
                                                    <option
                                                        key={`${user.id}-${role}`}
                                                        value={role}
                                                    >
                                                        {toLabel(role)}
                                                    </option>
                                                ))}
                                            </select>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="outline"
                                            >
                                                Save Role
                                            </Button>
                                        </Form>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Role Distribution</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {Object.entries(roleCounts).map(([role, count]) => (
                            <Badge key={role} variant="outline" className="px-3 py-1">
                                {toLabel(role)}: {count}
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {users.from ?? 0} to {users.to ?? 0} of{' '}
                        {users.total.toLocaleString()} users
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={users.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                users.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={users.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                users.next_page_url === null
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
