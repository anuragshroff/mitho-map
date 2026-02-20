import { Form, Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
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
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    destroy as adminBannersDestroy,
    index as adminBannersIndex,
    store as adminBannersStore,
    updateStatus as adminBannersUpdateStatus,
} from '@/routes/admin/banners';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Banners',
        href: adminBannersIndex().url,
    },
];

type BannerRow = {
    id: number;
    title: string;
    image_url: string;
    target_url: string | null;
    is_active: boolean;
    order: number;
    created_at: string;
};

type PaginatedBanners = {
    data: BannerRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type BannersProps = {
    banners: PaginatedBanners;
};

export default function AdminBanners({ banners }: BannersProps) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    
    const createForm = useForm({
        title: '',
        image_url: '',
        target_url: '',
        is_active: true,
        order: 0,
    });

    const submitCreate: FormEventHandler = (e) => {
        e.preventDefault();
        createForm.submit(adminBannersStore(), {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Banners" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-lg font-medium tracking-tight">Banners</h2>
                        <p className="text-sm text-muted-foreground">
                            Manage home screen banners
                        </p>
                    </div>

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>Create Banner</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Create Banner</DialogTitle>
                                <DialogDescription>
                                    Add a new banner to the home screen.
                                </DialogDescription>
                            </DialogHeader>

                            <form onSubmit={submitCreate} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        value={createForm.data.title}
                                        onChange={(e) => createForm.setData('title', e.target.value)}
                                        required
                                    />
                                    {createForm.errors.title && (
                                        <p className="text-sm text-destructive">{createForm.errors.title}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="image_url">Image URL</Label>
                                    <Input
                                        id="image_url"
                                        type="url"
                                        value={createForm.data.image_url}
                                        onChange={(e) => createForm.setData('image_url', e.target.value)}
                                        required
                                    />
                                    {createForm.errors.image_url && (
                                        <p className="text-sm text-destructive">{createForm.errors.image_url}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="target_url">Target URL (Optional)</Label>
                                    <Input
                                        id="target_url"
                                        type="url"
                                        value={createForm.data.target_url}
                                        onChange={(e) => createForm.setData('target_url', e.target.value)}
                                    />
                                    {createForm.errors.target_url && (
                                        <p className="text-sm text-destructive">{createForm.errors.target_url}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="order">Sort Order</Label>
                                    <Input
                                        id="order"
                                        type="number"
                                        min="0"
                                        value={createForm.data.order}
                                        onChange={(e) => createForm.setData('order', Number(e.target.value))}
                                    />
                                    {createForm.errors.order && (
                                        <p className="text-sm text-destructive">{createForm.errors.order}</p>
                                    )}
                                </div>
                                
                                <div className="flex justify-end pt-4">
                                    <Button type="submit" disabled={createForm.processing}>
                                        Save Banner
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Manage Banners</CardTitle>
                        <CardDescription>
                            {banners.total.toLocaleString()} banners created
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {banners.data.map((banner) => (
                            <div
                                key={banner.id}
                                className="grid gap-3 rounded-lg border p-3 md:grid-cols-[200px_1fr_auto] md:items-center"
                            >
                                <img src={banner.image_url} alt={banner.title} className="h-24 w-full object-cover rounded-md" />
                                <div>
                                    <p className="font-medium text-lg">
                                        {banner.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Order: {banner.order} •{' '}
                                        {banner.target_url ? (
                                            <a href={banner.target_url} target="_blank" rel="noreferrer" className="text-blue-500 hover:underline">
                                                Link
                                            </a>
                                        ) : 'No Link'}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant={
                                            banner.is_active
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {banner.is_active ? 'Active' : 'Inactive'}
                                    </Badge>

                                    <Form
                                        action={
                                            adminBannersUpdateStatus({
                                                banner: banner.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value={banner.is_active ? '0' : '1'}
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="outline"
                                        >
                                            {banner.is_active
                                                ? 'Deactivate'
                                                : 'Activate'}
                                        </Button>
                                    </Form>

                                    <Form
                                        action={
                                            adminBannersDestroy({
                                                banner: banner.id,
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
                        Showing {banners.from ?? 0} to {banners.to ?? 0} of{' '}
                        {banners.total.toLocaleString()} item{banners.total !== 1 ? 's' : ''}
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={banners.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                banners.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={banners.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                banners.next_page_url === null
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
