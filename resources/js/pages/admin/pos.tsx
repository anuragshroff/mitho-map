import { Form, Head } from '@inertiajs/react';
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
import { store as adminPosStore } from '@/routes/admin/pos/index';
import type { BreadcrumbItem } from '@/types';
import { useState } from 'react';
import { Search, ShoppingCart, Trash2, User as UserIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin overview',
        href: adminDashboard().url,
    },
    {
        title: 'POS (Point of Sale)',
        href: '/admin/pos',
    },
];

type Restaurant = {
    id: number;
    name: string;
};

type MenuItem = {
    id: number;
    name: string;
    price_cents: number;
    restaurant_id: number;
    restaurant_name: string;
};

type Customer = {
    id: number;
    name: string;
    phone: string | null;
};

type CartItem = {
    menu_item_id: number;
    name: string;
    price_cents: number;
    quantity: number;
};

type POSProps = {
    restaurants: Restaurant[];
    menuItems: MenuItem[];
    customers: Customer[];
};

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

function formatMoney(cents: number): string {
    return currency.format(cents / 100);
}

export default function AdminPosPage({
    restaurants,
    menuItems,
    customers,
}: POSProps) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [search, setSearch] = useState('');
    const [selectedRestaurant, setSelectedRestaurant] = useState<number | ''>('');
    const [selectedCustomerId, setSelectedCustomerId] = useState<number | ''>('');
    const [deliveryAddress, setDeliveryAddress] = useState('Dine-in / Guest');
    const [customerNotes, setCustomerNotes] = useState('');

    const filteredItems = menuItems.filter((item) => {
        const matchesSearch = item.name.toLowerCase().includes(search.toLowerCase());
        const matchesRestaurant = selectedRestaurant === '' || item.restaurant_id === selectedRestaurant;
        return matchesSearch && matchesRestaurant;
    });

    const addToCart = (item: MenuItem) => {
        setCart((prev) => {
            const existing = prev.find((i) => i.menu_item_id === item.id);
            if (existing) {
                return prev.map((i) =>
                    i.menu_item_id === item.id ? { ...i, quantity: i.quantity + 1 } : i
                );
            }
            return [...prev, { menu_item_id: item.id, name: item.name, price_cents: item.price_cents, quantity: 1 }];
        });
    };

    const removeFromCart = (menuItemId: number) => {
        setCart((prev) => prev.filter((i) => i.menu_item_id !== menuItemId));
    };

    const updateQuantity = (menuItemId: number, delta: number) => {
        setCart((prev) =>
            prev.map((i) => {
                if (i.menu_item_id === menuItemId) {
                    const newQty = Math.max(1, i.quantity + delta);
                    return { ...i, quantity: newQty };
                }
                return i;
            })
        );
    };

    const subtotal = cart.reduce((acc, item) => acc + item.price_cents * item.quantity, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin POS" />

            <div className="flex h-[calc(100vh-8rem)] flex-col gap-4 p-4 lg:flex-row">
                {/* Product Section */}
                <div className="flex flex-1 flex-col gap-4 overflow-hidden">
                    <Card className="shrink-0">
                        <CardHeader className="pb-3">
                            <CardTitle>Menu Catalog</CardTitle>
                            <CardDescription>Select items to add to the order</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                <div className="relative flex-1 min-w-[200px]">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search menu items..."
                                        className="pl-8"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                </div>
                                <select
                                    className="h-9 rounded-md border bg-transparent px-3 text-sm"
                                    value={selectedRestaurant}
                                    onChange={(e) => setSelectedRestaurant(e.target.value ? Number(e.target.value) : '')}
                                >
                                    <option value="">All Restaurants</option>
                                    {restaurants.map((r) => (
                                        <option key={r.id} value={r.id}>{r.name}</option>
                                    ))}
                                </select>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid flex-1 gap-4 overflow-y-auto pr-2 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {filteredItems.map((item) => (
                            <Card key={item.id} className="flex flex-col hover:border-primary transition-colors cursor-pointer" onClick={() => addToCart(item)}>
                                <CardHeader className="p-4 pb-2">
                                    <Badge variant="outline" className="w-fit mb-1">{item.restaurant_name}</Badge>
                                    <CardTitle className="text-sm line-clamp-1">{item.name}</CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-1 items-end justify-between p-4 pt-0">
                                    <span className="font-semibold text-primary">{formatMoney(item.price_cents)}</span>
                                    <Button size="sm" variant="secondary" className="h-8">Add</Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Checkout Section */}
                <div className="flex w-full flex-col gap-4 lg:w-[400px]">
                    <Card className="flex flex-1 flex-col overflow-hidden">
                        <CardHeader className="shrink-0">
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2">
                                    <ShoppingCart className="h-5 w-5" />
                                    Current Order
                                </CardTitle>
                                <Badge variant="secondary">{cart.length} items</Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-1 flex-col gap-4 overflow-hidden">
                            <div className="flex-1 overflow-y-auto space-y-4">
                                {cart.length === 0 ? (
                                    <div className="flex h-full flex-col items-center justify-center text-muted-foreground opacity-50">
                                        <ShoppingCart className="mb-2 h-12 w-12" />
                                        <p>Your cart is empty</p>
                                    </div>
                                ) : (
                                    cart.map((item) => (
                                        <div key={item.menu_item_id} className="flex items-center justify-between gap-2 border-b pb-3">
                                            <div className="flex flex-col gap-1 min-w-0">
                                                <p className="text-sm font-medium line-clamp-1">{item.name}</p>
                                                <p className="text-xs text-muted-foreground">{formatMoney(item.price_cents)}</p>
                                            </div>
                                            <div className="flex items-center gap-2 shrink-0">
                                                <div className="flex items-center rounded-md border">
                                                    <button className="px-2 py-1 text-lg hover:bg-accent" onClick={() => updateQuantity(item.menu_item_id, -1)}>-</button>
                                                    <span className="w-8 text-center text-sm">{item.quantity}</span>
                                                    <button className="px-2 py-1 text-lg hover:bg-accent" onClick={() => updateQuantity(item.menu_item_id, 1)}>+</button>
                                                </div>
                                                <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" onClick={() => removeFromCart(item.menu_item_id)}>
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <div className="shrink-0 space-y-4 pt-4 border-t">
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2 text-sm">
                                        <UserIcon className="h-4 w-4 text-muted-foreground" />
                                        <select
                                            className="h-9 w-full rounded-md border bg-transparent px-3"
                                            value={selectedCustomerId}
                                            onChange={(e) => setSelectedCustomerId(e.target.value ? Number(e.target.value) : '')}
                                        >
                                            <option value="">Guest Customer</option>
                                            {customers.map((c) => (
                                                <option key={c.id} value={c.id}>{c.name} {c.phone ? `(${c.phone})` : ''}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <Input
                                        placeholder="Delivery Address / Table #"
                                        value={deliveryAddress}
                                        onChange={(e) => setDeliveryAddress(e.target.value)}
                                    />
                                    <Input
                                        placeholder="Order Notes (optional)"
                                        value={customerNotes}
                                        onChange={(e) => setCustomerNotes(e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Subtotal</span>
                                        <span>{formatMoney(subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between font-bold text-lg border-t pt-2">
                                        <span>Total</span>
                                        <span className="text-primary">{formatMoney(subtotal)}</span>
                                    </div>
                                </div>

                                <Form
                                    {...(adminPosStore as any).form({
                                        restaurant_id: selectedRestaurant || (cart[0]?.menu_item_id ? menuItems.find(m => m.id === cart[0].menu_item_id)?.restaurant_id : ''),
                                        customer_id: selectedCustomerId || null,
                                        items: cart.map(item => ({ menu_item_id: item.menu_item_id, quantity: item.quantity })),
                                        delivery_address: deliveryAddress,
                                        customer_notes: customerNotes
                                    })}
                                    className="w-full"
                                >
                                    <Button className="w-full py-6 text-lg font-bold" disabled={cart.length === 0 || !selectedRestaurant && cart.length > 0 && !cart.every(i => menuItems.find(m => m.id === i.menu_item_id)?.restaurant_id === menuItems.find(m => m.id === cart[0].menu_item_id)?.restaurant_id)}>
                                        Complete Order
                                    </Button>
                                    {!selectedRestaurant && cart.length > 0 && Array.from(new Set(cart.map(i => menuItems.find(m => m.id === i.menu_item_id)?.restaurant_id))).length > 1 && (
                                        <p className="text-[10px] text-destructive mt-1 text-center">Please filter by a single restaurant to checkout</p>
                                    )}
                                </Form>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
