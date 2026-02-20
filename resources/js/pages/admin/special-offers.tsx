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
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    destroy as adminSpecialOffersDestroy,
    index as adminSpecialOffersIndex,
    store as adminSpecialOffersStore,
    updateStatus as adminSpecialOffersUpdateStatus,
} from '@/routes/admin/special-offers';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'Special Offers',
        href: adminSpecialOffersIndex().url,
    },
];

type RestaurantRow = {
    id: number;
    name: string;
};

type SpecialOfferRow = {
    id: number;
    restaurant_id: number;
    restaurant: RestaurantRow;
    title: string;
    description: string | null;
    discount_percentage: number | null;
    discount_amount: number | null;
    valid_from: string | null;
    valid_until: string | null;
    is_active: boolean;
    created_at: string;
};

type PaginatedSpecialOffers = {
    data: SpecialOfferRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type SpecialOffersProps = {
    offers: PaginatedSpecialOffers;
    restaurants: RestaurantRow[];
};

function formatDate(iso: string | null): string {
    if (!iso) return 'Forever';
    return new Date(iso).toLocaleDateString();
}

export default function AdminSpecialOffers({ offers, restaurants }: SpecialOffersProps) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    
    const createForm = useForm({
        restaurant_id: '',
        title: '',
        description: '',
        discount_percentage: '',
        discount_amount: '',
        valid_from: '',
        valid_until: '',
        is_active: true,
    });

    const submitCreate: FormEventHandler = (e) => {
        e.preventDefault();
        createForm.submit(adminSpecialOffersStore(), {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Special Offers" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-lg font-medium tracking-tight">Special Offers</h2>
                        <p className="text-sm text-muted-foreground">
                            Manage limited time offers and discounts for restaurants
                        </p>
                    </div>

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>Create Offer</Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Create Special Offer</DialogTitle>
                                <DialogDescription>
                                    Add a new discount deal.
                                </DialogDescription>
                            </DialogHeader>

                            <form onSubmit={submitCreate} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="restaurant_id">Restaurant</Label>
                                    <Select 
                                        onValueChange={(val) => createForm.setData('restaurant_id', val)} 
                                        defaultValue={createForm.data.restaurant_id}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a restaurant" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {restaurants.map(r => (
                                                <SelectItem key={r.id} value={r.id.toString()}>{r.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {createForm.errors.restaurant_id && (
                                        <p className="text-sm text-destructive">{createForm.errors.restaurant_id}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        value={createForm.data.title}
                                        onChange={(e: any) => createForm.setData('title', e.target.value)}
                                        required
                                    />
                                    {createForm.errors.title && (
                                        <p className="text-sm text-destructive">{createForm.errors.title}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description (Optional)</Label>
                                    <Textarea
                                        id="description"
                                        value={createForm.data.description}
                                        onChange={(e: any) => createForm.setData('description', e.target.value)}
                                    />
                                    {createForm.errors.description && (
                                        <p className="text-sm text-destructive">{createForm.errors.description}</p>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="discount_percentage">Discount %</Label>
                                        <Input
                                            id="discount_percentage"
                                            type="number"
                                            step="0.01"
                                            max="100"
                                            value={createForm.data.discount_percentage}
                                            onChange={(e: any) => createForm.setData('discount_percentage', e.target.value)}
                                        />
                                        {createForm.errors.discount_percentage && (
                                            <p className="text-sm text-destructive">{createForm.errors.discount_percentage}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="discount_amount">Discount Amount</Label>
                                        <Input
                                            id="discount_amount"
                                            type="number"
                                            step="0.01"
                                            value={createForm.data.discount_amount}
                                            onChange={(e: any) => createForm.setData('discount_amount', e.target.value)}
                                        />
                                        {createForm.errors.discount_amount && (
                                            <p className="text-sm text-destructive">{createForm.errors.discount_amount}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="valid_from">Valid From (Optional)</Label>
                                        <Input
                                            id="valid_from"
                                            type="date"
                                            value={createForm.data.valid_from}
                                            onChange={(e: any) => createForm.setData('valid_from', e.target.value)}
                                        />
                                        {createForm.errors.valid_from && (
                                            <p className="text-sm text-destructive">{createForm.errors.valid_from}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="valid_until">Valid Until (Optional)</Label>
                                        <Input
                                            id="valid_until"
                                            type="date"
                                            value={createForm.data.valid_until}
                                            onChange={(e) => createForm.setData('valid_until', e.target.value)}
                                        />
                                        {createForm.errors.valid_until && (
                                            <p className="text-sm text-destructive">{createForm.errors.valid_until}</p>
                                        )}
                                    </div>
                                </div>
                                
                                <div className="flex justify-end pt-4">
                                    <Button type="submit" disabled={createForm.processing}>
                                        Save Offer
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Manage Special Offers</CardTitle>
                        <CardDescription>
                            {offers.total.toLocaleString()} offers 
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {offers.data.map((offer) => (
                            <div
                                key={offer.id}
                                className="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_auto] md:items-center"
                            >
                                <div>
                                    <div className="flex items-center gap-2 mb-1">
                                        <p className="font-semibold text-lg">{offer.title}</p>
                                        {offer.discount_percentage ? (
                                            <Badge variant="secondary">{offer.discount_percentage}% OFF</Badge>
                                        ) : null}
                                        {offer.discount_amount ? (
                                            <Badge variant="secondary">${offer.discount_amount} OFF</Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Restaurant: <strong>{offer.restaurant.name}</strong>
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {formatDate(offer.valid_from)} - {formatDate(offer.valid_until)}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2 mt-2 md:mt-0">
                                    <Badge
                                        variant={
                                            offer.is_active
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {offer.is_active ? 'Active' : 'Inactive'}
                                    </Badge>

                                    <Form
                                        action={
                                            adminSpecialOffersUpdateStatus({
                                                specialOffer: offer.id,
                                            }).url
                                        }
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value={offer.is_active ? '0' : '1'}
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="outline"
                                        >
                                            {offer.is_active
                                                ? 'Deactivate'
                                                : 'Activate'}
                                        </Button>
                                    </Form>

                                    <Form
                                        action={
                                            adminSpecialOffersDestroy({
                                                specialOffer: offer.id,
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
                        {offers.data.length === 0 && (
                            <p className="text-center text-muted-foreground py-8">
                                No special offers found.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Showing {offers.from ?? 0} to {offers.to ?? 0} of{' '}
                        {offers.total.toLocaleString()} item{offers.total !== 1 ? 's' : ''}
                    </p>

                    <div className="flex items-center gap-2">
                        <Link
                            href={offers.prev_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                offers.prev_page_url === null
                                    ? 'pointer-events-none opacity-50'
                                    : 'hover:bg-accent'
                            }`}
                        >
                            Previous
                        </Link>
                        <Link
                            href={offers.next_page_url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                offers.next_page_url === null
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
