<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminCouponRequest;
use App\Http\Requests\Admin\UpdateAdminCouponRequest;
use App\Http\Requests\Admin\UpdateAdminCouponStatusRequest;
use App\Models\Coupon;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCouponController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Coupon::query()
            ->with(['restaurant:id,name'])
            ->latest('id');

        $search = trim($request->string('search')->toString());
        $restaurantId = $request->string('restaurant_id')->toString();
        $isActive = $request->string('is_active')->toString();
        $discountType = $request->string('discount_type')->toString();

        if ($search !== '') {
            $query->where(function ($couponQuery) use ($search): void {
                $couponQuery
                    ->where('code', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search): void {
                        $restaurantQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($restaurantId !== '' && ctype_digit($restaurantId)) {
            $query->where('restaurant_id', (int) $restaurantId);
        }

        if (in_array($isActive, ['1', '0'], true)) {
            $query->where('is_active', $isActive === '1');
        }

        if ($discountType !== '' && in_array($discountType, $this->discountTypeValues(), true)) {
            $query->where('discount_type', $discountType);
        }

        $coupons = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Coupon $coupon): array {
                return [
                    'id' => $coupon->id,
                    'restaurant_id' => $coupon->restaurant_id,
                    'restaurant_name' => $coupon->restaurant?->name,
                    'code' => $coupon->code,
                    'title' => $coupon->title,
                    'description' => $coupon->description,
                    'discount_type' => $coupon->discount_type?->value,
                    'discount_value' => $coupon->discount_value,
                    'minimum_order_cents' => $coupon->minimum_order_cents,
                    'maximum_discount_cents' => $coupon->maximum_discount_cents,
                    'starts_at' => $coupon->starts_at?->toIso8601String(),
                    'ends_at' => $coupon->ends_at?->toIso8601String(),
                    'usage_limit' => $coupon->usage_limit,
                    'usage_count' => $coupon->usage_count,
                    'is_active' => $coupon->is_active,
                ];
            });

        $restaurantOptions = Restaurant::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Restaurant $restaurant): array {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                ];
            })
            ->values()
            ->all();

        $activeCounts = Coupon::query()
            ->selectRaw('is_active, COUNT(*) AS aggregate')
            ->groupBy('is_active')
            ->pluck('aggregate', 'is_active')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        return Inertia::render('admin/coupons', [
            'coupons' => $coupons,
            'restaurantOptions' => $restaurantOptions,
            'discountTypeOptions' => $this->discountTypeValues(),
            'activeCounts' => $activeCounts,
            'filters' => [
                'search' => $search,
                'restaurant_id' => $restaurantId,
                'is_active' => $isActive,
                'discount_type' => $discountType,
            ],
        ]);
    }

    public function store(StoreAdminCouponRequest $request): RedirectResponse
    {
        Coupon::query()->create($request->validated());

        return back();
    }

    public function update(
        UpdateAdminCouponRequest $request,
        Coupon $coupon,
    ): RedirectResponse {
        $coupon->update($request->validated());

        return back();
    }

    public function updateStatus(
        UpdateAdminCouponStatusRequest $request,
        Coupon $coupon,
    ): RedirectResponse {
        $coupon->is_active = $request->boolean('is_active');
        $coupon->save();

        return back();
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back();
    }

    /**
     * @return array<int, string>
     */
    protected function discountTypeValues(): array
    {
        return collect(CouponDiscountType::cases())
            ->map(fn (CouponDiscountType $discountType): string => $discountType->value)
            ->values()
            ->all();
    }
}
