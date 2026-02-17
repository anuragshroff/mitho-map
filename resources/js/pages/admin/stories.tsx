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
    destroy as adminStoriesDestroy,
    index as adminStoriesIndex,
    updateStatus as adminStoriesUpdateStatus,
} from '@/routes/admin/stories';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Stories',
        href: adminStoriesIndex().url,
    },
];

type StoryRow = {
    id: number;
    restaurant_name: string | null;
    creator_name: string | null;
    caption: string | null;
    media_url: string;
    expires_at: string | null;
    is_active: boolean;
};

type PaginatedStories = {
    data: StoryRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type StoryFilters = {
    search: string;
    is_active: string;
};

type StoriesProps = {
    stories: PaginatedStories;
    filters: StoryFilters;
};

function formatDate(iso: string | null): string {
    if (iso === null) {
        return 'Not available';
    }

    return new Date(iso).toLocaleString();
}

export default function AdminStories({ stories, filters }: StoriesProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin stories" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Stories Feed</CardTitle>
                        <CardDescription>
                            {stories.total.toLocaleString()} stories published
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={adminStoriesIndex().url}
                            method="get"
                            options={{ preserveScroll: true }}
                            className="grid gap-2 md:grid-cols-[1fr_220px_auto_auto]"
                        >
                            <Input
                                name="search"
                                placeholder="Search by caption, restaurant, creator"
                                defaultValue={filters.search}
                            />
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
                                href={adminStoriesIndex()}
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
                        <CardTitle>Moderate Stories</CardTitle>
                        <CardDescription>
                            Activate, deactivate, or remove story content
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {stories.data.map((story) => (
                            <div
                                key={story.id}
                                className="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_auto] md:items-center"
                            >
                                <div>
                                    <p className="text-sm font-medium">
                                        {story.restaurant_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        By {story.creator_name ?? 'Unknown'} •
                                        Expires {formatDate(story.expires_at)}
                                    </p>
                                    <p className="mt-1 line-clamp-1 text-sm text-muted-foreground">
                                        {story.caption ?? 'No caption'}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant={
                                            story.is_active
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {story.is_active ? 'Active' : 'Inactive'}
                                    </Badge>

                                    <Form
                                        action={
                                            adminStoriesUpdateStatus({
                                                story: story.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value={
                                                story.is_active ? '0' : '1'
                                            }
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="outline"
                                        >
                                            {story.is_active
                                                ? 'Deactivate'
                                                : 'Activate'}
                                        </Button>
                                    </Form>

                                    <Form
                                        action={
                                            adminStoriesDestroy({
                                                story: story.id,
                                            }).url
                                        }
                                        method="delete"
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="destructive"
                                        >
                                            Delete
                                        </Button>
                                    </Form>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {stories.from ?? 0} to {stories.to ?? 0} of{' '}
                        {stories.total.toLocaleString()} stories
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={stories.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                stories.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={stories.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                stories.next_page_url === null
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
