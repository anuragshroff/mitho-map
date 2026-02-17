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
    destroy as adminMenuItemsDestroy,
    index as adminMenuItemsIndex,
    store as adminMenuItemsStore,
    update as adminMenuItemsUpdate,
    updateAvailability as adminMenuItemsUpdateAvailability,
} from '@/routes/admin/menu-items';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Menu Items',
        href: adminMenuItemsIndex().url,
    },
];

type RestaurantOption = {
    id: number;
    name: string;
};

type MenuItemRow = {
    id: number;
    restaurant_id: number;
    restaurant_name: string | null;
    restaurant_slug: string | null;
    owner_name: string | null;
    name: string;
    description: string | null;
    price_cents: number;
    prep_time_minutes: number;
    is_available: boolean;
    order_items_count: number;
};

type PaginatedMenuItems = {
    data: MenuItemRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type MenuItemFilters = {
    search: string;
    restaurant_id: string;
    is_available: string;
};

type MenuItemsProps = {
    menuItems: PaginatedMenuItems;
    restaurantOptions: RestaurantOption[];
    availabilityCounts: Record<string, number>;
    filters: MenuItemFilters;
};

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

function formatMoney(cents: number): string {
    return currency.format(cents / 100);
}

export default function AdminMenuItems({
    menuItems,
    restaurantOptions,
    availabilityCounts,
    filters,
}: MenuItemsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin menu items" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Menu Catalog</CardTitle>
                        <CardDescription>
                            {menuItems.total.toLocaleString()} menu items across restaurants
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminMenuItemsIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 lg:grid-cols-[1fr_220px_180px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by item, description, restaurant"
                                defaultValue={filters.search}
                            />
                            <select
                                name="restaurant_id"
                                defaultValue={filters.restaurant_id}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">All restaurants</option>
                                {restaurantOptions.map((restaurant) => (
                                    <option key={restaurant.id} value={restaurant.id}>
                                        {restaurant.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="is_available"
                                defaultValue={filters.is_available}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">Available + Unavailable</option>
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                            <Button type="submit" variant="secondary">
                                Apply
                            </Button>
                            <Link
                                href={adminMenuItemsIndex()}
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
                        <CardTitle>Add Menu Item</CardTitle>
                        <CardDescription>
                            Create a new catalog item for any restaurant
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminMenuItemsStore().url}
                            method="post"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 lg:grid-cols-6"
                        >
                            <select
                                name="restaurant_id"
                                defaultValue=""
                                required
                                className="h-9 rounded-md border bg-transparent px-3 text-sm lg:col-span-2"
                            >
                                <option value="" disabled>
                                    Select restaurant
                                </option>
                                {restaurantOptions.map((restaurant) => (
                                    <option key={`create-${restaurant.id}`} value={restaurant.id}>
                                        {restaurant.name}
                                    </option>
                                ))}
                            </select>
                            <Input name="name" placeholder="Item name" required className="lg:col-span-2" />
                            <Input name="price_cents" type="number" min={1} placeholder="Price cents" required />
                            <Input
                                name="prep_time_minutes"
                                type="number"
                                min={1}
                                placeholder="Prep mins"
                                defaultValue="15"
                                required
                            />
                            <Input
                                name="description"
                                placeholder="Description"
                                className="lg:col-span-4"
                            />
                            <select
                                name="is_available"
                                defaultValue="1"
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                            <Button type="submit" className="lg:col-span-1">
                                Create Item
                            </Button>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Manage Menu Items</CardTitle>
                        <CardDescription>
                            Update pricing, prep times, and availability
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {menuItems.data.map((menuItem) => (
                            <div key={menuItem.id} className="space-y-3 rounded-lg border p-3">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p className="text-sm font-medium">{menuItem.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {menuItem.restaurant_name} {' • '}Owner: {menuItem.owner_name ?? 'N/A'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {menuItem.description ?? 'No description'}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Badge variant={menuItem.is_available ? 'default' : 'outline'}>
                                            {menuItem.is_available ? 'Available' : 'Unavailable'}
                                        </Badge>
                                        <Badge variant="outline">
                                            Ordered: {menuItem.order_items_count}
                                        </Badge>
                                        <p className="text-sm font-semibold">
                                            {formatMoney(menuItem.price_cents)}
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-2 lg:grid-cols-[1fr_auto_auto]">
                                    <Form
                                        action={adminMenuItemsUpdate({ menuItem: menuItem.id }).url}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        className="grid gap-2 md:grid-cols-5"
                                    >
                                        <Input
                                            name="name"
                                            defaultValue={menuItem.name}
                                            required
                                            className="md:col-span-2"
                                        />
                                        <Input
                                            name="price_cents"
                                            type="number"
                                            min={1}
                                            defaultValue={menuItem.price_cents}
                                            required
                                        />
                                        <Input
                                            name="prep_time_minutes"
                                            type="number"
                                            min={1}
                                            defaultValue={menuItem.prep_time_minutes}
                                            required
                                        />
                                        <select
                                            name="is_available"
                                            defaultValue={menuItem.is_available ? '1' : '0'}
                                            className="h-9 rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            <option value="1">Available</option>
                                            <option value="0">Unavailable</option>
                                        </select>
                                        <Input
                                            name="description"
                                            defaultValue={menuItem.description ?? ''}
                                            className="md:col-span-4"
                                        />
                                        <Button type="submit" variant="outline" size="sm">
                                            Save
                                        </Button>
                                    </Form>

                                    <Form
                                        action={
                                            adminMenuItemsUpdateAvailability({
                                                menuItem: menuItem.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_available"
                                            value={menuItem.is_available ? '0' : '1'}
                                        />
                                        <Button type="submit" variant="outline" size="sm">
                                            {menuItem.is_available ? 'Mark Unavailable' : 'Mark Available'}
                                        </Button>
                                    </Form>

                                    <Form
                                        action={adminMenuItemsDestroy({ menuItem: menuItem.id }).url}
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
                        <CardTitle>Availability Distribution</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <Badge variant="outline" className="px-3 py-1">
                            Available: {availabilityCounts['1'] ?? 0}
                        </Badge>
                        <Badge variant="outline" className="px-3 py-1">
                            Unavailable: {availabilityCounts['0'] ?? 0}
                        </Badge>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {menuItems.from ?? 0} to {menuItems.to ?? 0} of{' '}
                        {menuItems.total.toLocaleString()} items
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={menuItems.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                menuItems.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={menuItems.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                menuItems.next_page_url === null
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
