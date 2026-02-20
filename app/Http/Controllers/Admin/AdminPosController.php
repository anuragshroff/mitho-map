<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPosController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurants = Restaurant::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $menuItems = MenuItem::query()
            ->with('restaurant:id,name')
            ->where('is_available', true)
            ->orderBy('name')
            ->get()
            ->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price_cents' => $item->price_cents,
                'restaurant_id' => $item->restaurant_id,
                'restaurant_name' => $item->restaurant?->name,
            ]);

        $customers = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return Inertia::render('admin/pos', [
            'restaurants' => $restaurants,
            'menuItems' => $menuItems,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'delivery_address' => ['required', 'string'],
            'customer_notes' => ['nullable', 'string'],
        ]);

        $order = Order::query()->create([
            'restaurant_id' => $validated['restaurant_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'customer_notes' => $validated['customer_notes'],
            'status' => \App\Enums\OrderStatus::Pending,
            'subtotal_cents' => 0, // Will update below
            'total_cents' => 0,    // Will update below
            'placed_at' => now(),
        ]);

        $subtotal = 0;

        foreach ($validated['items'] as $itemData) {
            $menuItem = MenuItem::findOrFail($itemData['menu_item_id']);
            $lineTotal = $menuItem->price_cents * $itemData['quantity'];
            $subtotal += $lineTotal;

            $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'unit_price_cents' => $menuItem->price_cents,
                'quantity' => $itemData['quantity'],
                'line_total_cents' => $lineTotal,
            ]);
        }

        $order->update([
            'subtotal_cents' => $subtotal,
            'total_cents' => $subtotal, // For now assuming no tax/fee for POS or handle later
        ]);

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }
}
