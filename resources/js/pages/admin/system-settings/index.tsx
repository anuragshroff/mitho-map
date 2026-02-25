import { Head, useForm } from '@inertiajs/react';
import { Settings as SettingsIcon, Truck, CreditCard, Save } from 'lucide-react';
import { FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { update } from '@/actions/App/Http/Controllers/Admin/AdminSystemSettingController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { toast } from 'sonner';

interface SystemSettingsProps {
    settings: {
        delivery?: Record<string, string>;
        payment?: Record<string, string>;
        driver?: Record<string, string>;
    };
}

const breadcrumbs = [
    {
        title: 'Admin Dashboard',
        href: '/admin',
    },
    {
        title: 'System Settings',
        href: '/admin/system-settings',
    },
];

export default function SystemSettings({ settings }: SystemSettingsProps) {
    const deliverySettings = settings.delivery || {};
    const paymentSettings = settings.payment || {};

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        settings: [
            // Delivery
            { key: 'delivery_base_fee_cents', value: deliverySettings['delivery_base_fee_cents'] || '3000' },
            { key: 'delivery_per_km_cents', value: deliverySettings['delivery_per_km_cents'] || '1500' },
            { key: 'delivery_max_radius_km', value: deliverySettings['delivery_max_radius_km'] || '15' },
            
            { key: 'driver_max_radius_km', value: settings.driver?.['driver_max_radius_km'] || '10' },
            { key: 'driver_online_timeout_minutes', value: settings.driver?.['driver_online_timeout_minutes'] || '15' },

            // Payment
            { key: 'payment_methods_enabled', value: paymentSettings['payment_methods_enabled'] || 'cash' },
            { key: 'payment_esewa_merchant_id', value: paymentSettings['payment_esewa_merchant_id'] || '' },
            { key: 'payment_esewa_secret', value: paymentSettings['payment_esewa_secret'] || '' },
            { key: 'payment_khalti_public_key', value: paymentSettings['payment_khalti_public_key'] || '' },
            { key: 'payment_khalti_secret_key', value: paymentSettings['payment_khalti_secret_key'] || '' },
        ],
    });

    const updateSetting = (key: string, value: string) => {
        setData(
            'settings',
            data.settings.map((s) => (s.key === key ? { ...s, value } : s))
        );
    };

    const getValue = (key: string) => data.settings.find((s) => s.key === key)?.value || '';

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        post(update().url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Settings updated successfully');
            },
            onError: () => {
                toast.error('Failed to update settings');
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">System Settings</h1>
                        <p className="text-muted-foreground mt-1">Manage global configuration for delivery rules and payment integrations.</p>
                    </div>
                    <Button onClick={submit} disabled={processing} className="gap-2">
                        <Save className="h-4 w-4" />
                        Save All Changes
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Truck className="h-5 w-5" />
                                Delivery & Driver Rules
                            </CardTitle>
                            <CardDescription>Configure dynamic delivery pricing and driver assignment logic.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="delivery_base_fee">Base Delivery Fee (in cents)</Label>
                                <Input
                                    id="delivery_base_fee"
                                    type="number"
                                    value={getValue('delivery_base_fee_cents')}
                                    onChange={(e) => updateSetting('delivery_base_fee_cents', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">e.g., 3000 for Rs 30</p>
                            </div>
                            
                            <div className="space-y-2">
                                <Label htmlFor="delivery_per_km">Per Kilometer Fee (in cents)</Label>
                                <Input
                                    id="delivery_per_km"
                                    type="number"
                                    value={getValue('delivery_per_km_cents')}
                                    onChange={(e) => updateSetting('delivery_per_km_cents', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">Added to base fee per km. e.g., 1500 for Rs 15</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="delivery_max_radius">Max Customer Delivery Radius (km)</Label>
                                <Input
                                    id="delivery_max_radius"
                                    type="number"
                                    value={getValue('delivery_max_radius_km')}
                                    onChange={(e) => updateSetting('delivery_max_radius_km', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">Orders beyond this distance will be rejected.</p>
                            </div>

                            <div className="pt-4 border-t space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="driver_max_radius">Max Driver Assigment Radius (km)</Label>
                                    <Input
                                        id="driver_max_radius"
                                        type="number"
                                        value={getValue('driver_max_radius_km')}
                                        onChange={(e) => updateSetting('driver_max_radius_km', e.target.value)}
                                    />
                                    <p className="text-xs text-muted-foreground">Search radius for finding nearby drivers.</p>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="driver_timeout">Driver Online Timeout (minutes)</Label>
                                    <Input
                                        id="driver_timeout"
                                        type="number"
                                        value={getValue('driver_online_timeout_minutes')}
                                        onChange={(e) => updateSetting('driver_online_timeout_minutes', e.target.value)}
                                    />
                                    <p className="text-xs text-muted-foreground">How long a driver remains "online" after their last GPS ping.</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                Payment Gateways
                            </CardTitle>
                            <CardDescription>Configure credentials for digital payment providers.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="payment_methods">Enabled Methods</Label>
                                <Input
                                    id="payment_methods"
                                    value={getValue('payment_methods_enabled')}
                                    onChange={(e) => updateSetting('payment_methods_enabled', e.target.value)}
                                    placeholder="cash,esewa,khalti"
                                />
                                <p className="text-xs text-muted-foreground">Comma-separated list (e.g., cash,esewa,khalti)</p>
                            </div>

                            <div className="space-y-4 border p-4 rounded-lg bg-slate-50/50 dark:bg-slate-900/50">
                                <h3 className="font-semibold text-sm">eSewa Configuration</h3>
                                <div className="space-y-2">
                                    <Label htmlFor="esewa_merchant">Merchant ID (Product Code)</Label>
                                    <Input
                                        id="esewa_merchant"
                                        value={getValue('payment_esewa_merchant_id')}
                                        onChange={(e) => updateSetting('payment_esewa_merchant_id', e.target.value)}
                                        placeholder="EPAYTEST"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="esewa_secret">Secret Key</Label>
                                    <Input
                                        id="esewa_secret"
                                        type="password"
                                        value={getValue('payment_esewa_secret')}
                                        onChange={(e) => updateSetting('payment_esewa_secret', e.target.value)}
                                        placeholder="8gBm/:&EnhH.1/q"
                                    />
                                </div>
                            </div>

                            <div className="space-y-4 border p-4 rounded-lg bg-slate-50/50 dark:bg-slate-900/50">
                                <h3 className="font-semibold text-sm">Khalti Configuration</h3>
                                <div className="space-y-2">
                                    <Label htmlFor="khalti_public">Public Key</Label>
                                    <Input
                                        id="khalti_public"
                                        value={getValue('payment_khalti_public_key')}
                                        onChange={(e) => updateSetting('payment_khalti_public_key', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="khalti_secret">Secret Key</Label>
                                    <Input
                                        id="khalti_secret"
                                        type="password"
                                        value={getValue('payment_khalti_secret_key')}
                                        onChange={(e) => updateSetting('payment_khalti_secret_key', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
