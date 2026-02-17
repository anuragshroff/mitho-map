<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAdminCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'restaurant_id' => $this->input('restaurant_id') === '' ? null : $this->input('restaurant_id'),
            'code' => strtoupper(trim((string) $this->input('code', ''))),
            'minimum_order_cents' => $this->input('minimum_order_cents') === '' ? null : $this->input('minimum_order_cents'),
            'maximum_discount_cents' => $this->input('maximum_discount_cents') === '' ? null : $this->input('maximum_discount_cents'),
            'usage_limit' => $this->input('usage_limit') === '' ? null : $this->input('usage_limit'),
            'starts_at' => $this->input('starts_at') === '' ? null : $this->input('starts_at'),
            'ends_at' => $this->input('ends_at') === '' ? null : $this->input('ends_at'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');

        return [
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => ['required', 'integer', 'min:1', 'max:1000000'],
            'minimum_order_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'maximum_discount_cents' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var CouponDiscountType|null $discountType */
            $discountType = $this->enum('discount_type', CouponDiscountType::class);
            $discountValue = $this->integer('discount_value');

            if ($discountType === CouponDiscountType::Percentage && $discountValue > 100) {
                $validator->errors()->add('discount_value', 'Percentage discount cannot exceed 100.');
            }
        });
    }
}
