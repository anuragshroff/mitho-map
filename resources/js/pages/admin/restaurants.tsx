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
    index as adminRestaurantsIndex,
    updateAvailability as adminRestaurantsUpdateAvailability,
} from '@/routes/admin/restaurants';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Restaurants',
        href: adminRestaurantsIndex().url,
    },
];

type RestaurantRow = {
    id: number;
    name: string;
    slug: string;
    city: string;
    is_open: boolean;
    owner_name: string | null;
    menu_items_count: number;
    orders_count: number;
    stories_count: number;
};

type PaginatedRestaurants = {
    data: RestaurantRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type RestaurantFilters = {
    search: string;
    is_open: string;
};

type RestaurantsProps = {
    restaurants: PaginatedRestaurants;
    filters: RestaurantFilters;
};

export default function AdminRestaurants({
    restaurants,
    filters,
}: RestaurantsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin restaurants" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Restaurants</CardTitle>
                        <CardDescription>
                            {restaurants.total.toLocaleString()} restaurants in
                            the network
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminRestaurantsIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 md:grid-cols-[1fr_220px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by name, owner, city"
                                defaultValue={filters.search}
                            />
                            <select
                                name="is_open"
                                defaultValue={filters.is_open}
                                className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="">Open + Closed</option>
                                <option value="1">Open</option>
                                <option value="0">Closed</option>
                            </select>
                            <Button type="submit" variant="secondary">
                                Apply
                            </Button>
                            <Link
                                href={adminRestaurantsIndex()}
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
                        <CardTitle>Manage Restaurants</CardTitle>
                        <CardDescription>
                            Toggle ordering availability for each restaurant
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {restaurants.data.map((restaurant) => (
                            <div
                                key={restaurant.id}
                                className="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_auto] md:items-center"
                            >
                                <div>
                                    <p className="text-sm font-medium">
                                        {restaurant.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        @{restaurant.slug} • {restaurant.city}
                                        {' • '}Owner:{' '}
                                        {restaurant.owner_name ?? 'N/A'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Menu: {restaurant.menu_items_count} •
                                        Orders: {restaurant.orders_count} •
                                        Stories: {restaurant.stories_count}
                                    </p>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Badge
                                        variant={
                                            restaurant.is_open
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {restaurant.is_open ? 'Open' : 'Closed'}
                                    </Badge>

                                    <Form
                                        action={
                                            adminRestaurantsUpdateAvailability({
                                                restaurant: restaurant.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_open"
                                            value={
                                                restaurant.is_open ? '0' : '1'
                                            }
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="outline"
                                        >
                                            {restaurant.is_open
                                                ? 'Mark Closed'
                                                : 'Mark Open'}
                                        </Button>
                                    </Form>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {restaurants.from ?? 0} to {restaurants.to ?? 0}{' '}
                        of {restaurants.total.toLocaleString()} restaurants
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={restaurants.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                restaurants.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={restaurants.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                restaurants.next_page_url === null
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
