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
    destroy as adminCouponsDestroy,
    index as adminCouponsIndex,
    store as adminCouponsStore,
    update as adminCouponsUpdate,
    updateStatus as adminCouponsUpdateStatus,
} from '@/routes/admin/coupons';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Coupons',
        href: adminCouponsIndex().url,
    },
];

type RestaurantOption = {
    id: number;
    name: string;
};

type CouponRow = {
    id: number;
    restaurant_id: number | null;
    restaurant_name: string | null;
    code: string;
    title: string;
    description: string | null;
    discount_type: string | null;
    discount_value: number;
    minimum_order_cents: number | null;
    maximum_discount_cents: number | null;
    starts_at: string | null;
    ends_at: string | null;
    usage_limit: number | null;
    usage_count: number;
    is_active: boolean;
};

type PaginatedCoupons = {
    data: CouponRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type CouponFilters = {
    search: string;
    restaurant_id: string;
    is_active: string;
    discount_type: string;
};

type CouponsProps = {
    coupons: PaginatedCoupons;
    restaurantOptions: RestaurantOption[];
    discountTypeOptions: string[];
    activeCounts: Record<string, number>;
    filters: CouponFilters;
};

function toLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part[0]?.toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDate(iso: string | null): string {
    if (iso === null) {
        return 'Not set';
    }

    return new Date(iso).toLocaleString();
}

function toLocalDateTimeInput(iso: string | null): string {
    if (iso === null) {
        return '';
    }

    return iso.slice(0, 16);
}

function discountPreview(type: string | null, value: number): string {
    if (type === 'percentage') {
        return `${value}%`;
    }

    return `${value} cents`;
}

export default function AdminCoupons({
    coupons,
    restaurantOptions,
    discountTypeOptions,
    activeCounts,
    filters,
}: CouponsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin coupons" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Coupons</CardTitle>
                        <CardDescription>
                            {coupons.total.toLocaleString()} coupons configured
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminCouponsIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 lg:grid-cols-[1fr_220px_180px_180px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by code, title, restaurant"
                                defaultValue={filters.search}
                            />
                            <select
                                name="restaurant_id"
                                defaultValue={filters.restaurant_id}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">All restaurants + global</option>
                                {restaurantOptions.map((restaurant) => (
                                    <option key={restaurant.id} value={restaurant.id}>
                                        {restaurant.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="discount_type"
                                defaultValue={filters.discount_type}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">All discount types</option>
                                {discountTypeOptions.map((discountType) => (
                                    <option key={discountType} value={discountType}>
                                        {toLabel(discountType)}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="is_active"
                                defaultValue={filters.is_active}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">Active + Inactive</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <Button type="submit" variant="secondary">
                                Apply
                            </Button>
                            <Link
                                href={adminCouponsIndex()}
                                preserveScroll
                                className="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm hover:bg-accent"
                            >
                                Reset
                            </Link>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Create Coupon</CardTitle>
                        <CardDescription>
                            Add global or restaurant-specific coupon campaigns
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminCouponsStore().url}
                            method="post"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 lg:grid-cols-6"
                        >
                            <Input name="code" placeholder="Code (e.g. SAVE10)" required />
                            <Input name="title" placeholder="Coupon title" required className="lg:col-span-2" />
                            <select
                                name="discount_type"
                                defaultValue="percentage"
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                {discountTypeOptions.map((discountType) => (
                                    <option key={`create-${discountType}`} value={discountType}>
                                        {toLabel(discountType)}
                                    </option>
                                ))}
                            </select>
                            <Input
                                name="discount_value"
                                type="number"
                                min={1}
                                placeholder="Discount value"
                                required
                            />
                            <select
                                name="restaurant_id"
                                defaultValue=""
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">Global coupon</option>
                                {restaurantOptions.map((restaurant) => (
                                    <option key={`create-res-${restaurant.id}`} value={restaurant.id}>
                                        {restaurant.name}
                                    </option>
                                ))}
                            </select>
                            <Input name="description" placeholder="Description" className="lg:col-span-3" />
                            <Input
                                name="minimum_order_cents"
                                type="number"
                                min={0}
                                placeholder="Min order cents"
                            />
                            <Input
                                name="maximum_discount_cents"
                                type="number"
                                min={1}
                                placeholder="Max discount cents"
                            />
                            <Input
                                name="usage_limit"
                                type="number"
                                min={1}
                                placeholder="Usage limit"
                            />
                            <Input name="starts_at" type="datetime-local" />
                            <Input name="ends_at" type="datetime-local" />
                            <select
                                name="is_active"
                                defaultValue="1"
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <Button type="submit">Create Coupon</Button>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Manage Coupons</CardTitle>
                        <CardDescription>
                            Update offers, scheduling, and activation status
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {coupons.data.map((coupon) => (
                            <div key={coupon.id} className="space-y-3 rounded-lg border p-3">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {coupon.code} • {coupon.title}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {coupon.restaurant_name ?? 'Global coupon'}
                                            {' • '}Starts {formatDate(coupon.starts_at)}
                                            {' • '}Ends {formatDate(coupon.ends_at)}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {coupon.description ?? 'No description'}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={coupon.is_active ? 'default' : 'outline'}>
                                            {coupon.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                        <Badge variant="outline">
                                            {toLabel(coupon.discount_type ?? 'unknown')}:{' '}
                                            {discountPreview(coupon.discount_type, coupon.discount_value)}
                                        </Badge>
                                        <Badge variant="outline">
                                            Used: {coupon.usage_count}
                                            {coupon.usage_limit === null ? '' : ` / ${coupon.usage_limit}`}
                                        </Badge>
                                    </div>
                                </div>

                                <div className="grid gap-2 lg:grid-cols-[1fr_auto_auto]">
                                    <Form
                                        action={adminCouponsUpdate({ coupon: coupon.id }).url}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        className="grid gap-2 lg:grid-cols-6"
                                    >
                                        <Input name="code" defaultValue={coupon.code} required />
                                        <Input name="title" defaultValue={coupon.title} required className="lg:col-span-2" />
                                        <select
                                            name="discount_type"
                                            defaultValue={coupon.discount_type ?? 'percentage'}
                                            className="h-9 rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            {discountTypeOptions.map((discountType) => (
                                                <option key={`${coupon.id}-${discountType}`} value={discountType}>
                                                    {toLabel(discountType)}
                                                </option>
                                            ))}
                                        </select>
                                        <Input
                                            name="discount_value"
                                            type="number"
                                            min={1}
                                            defaultValue={coupon.discount_value}
                                            required
                                        />
                                        <select
                                            name="restaurant_id"
                                            defaultValue={coupon.restaurant_id?.toString() ?? ''}
                                            className="h-9 rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            <option value="">Global coupon</option>
                                            {restaurantOptions.map((restaurant) => (
                                                <option
                                                    key={`${coupon.id}-res-${restaurant.id}`}
                                                    value={restaurant.id}
                                                >
                                                    {restaurant.name}
                                                </option>
                                            ))}
                                        </select>
                                        <Input
                                            name="description"
                                            defaultValue={coupon.description ?? ''}
                                            className="lg:col-span-3"
                                        />
                                        <Input
                                            name="minimum_order_cents"
                                            type="number"
                                            min={0}
                                            defaultValue={coupon.minimum_order_cents ?? ''}
                                        />
                                        <Input
                                            name="maximum_discount_cents"
                                            type="number"
                                            min={1}
                                            defaultValue={coupon.maximum_discount_cents ?? ''}
                                        />
                                        <Input
                                            name="usage_limit"
                                            type="number"
                                            min={1}
                                            defaultValue={coupon.usage_limit ?? ''}
                                        />
                                        <Input
                                            name="starts_at"
                                            type="datetime-local"
                                            defaultValue={toLocalDateTimeInput(coupon.starts_at)}
                                        />
                                        <Input
                                            name="ends_at"
                                            type="datetime-local"
                                            defaultValue={toLocalDateTimeInput(coupon.ends_at)}
                                        />
                                        <select
                                            name="is_active"
                                            defaultValue={coupon.is_active ? '1' : '0'}
                                            className="h-9 rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        <Button type="submit" variant="outline" size="sm">
                                            Save
                                        </Button>
                                    </Form>

                                    <Form
                                        action={adminCouponsUpdateStatus({ coupon: coupon.id }).url}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value={coupon.is_active ? '0' : '1'}
                                        />
                                        <Button type="submit" variant="outline" size="sm">
                                            {coupon.is_active ? 'Deactivate' : 'Activate'}
                                        </Button>
                                    </Form>

                                    <Form
                                        action={adminCouponsDestroy({ coupon: coupon.id }).url}
                                        method="delete"
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button type="submit" variant="destructive" size="sm">
                                            Delete
                                        </Button>
                                    </Form>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Activation Distribution</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <Badge variant="outline" className="px-3 py-1">
                            Active: {activeCounts['1'] ?? 0}
                        </Badge>
                        <Badge variant="outline" className="px-3 py-1">
                            Inactive: {activeCounts['0'] ?? 0}
                        </Badge>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {coupons.from ?? 0} to {coupons.to ?? 0} of{' '}
                        {coupons.total.toLocaleString()} coupons
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={coupons.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                coupons.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={coupons.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                coupons.next_page_url === null
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
