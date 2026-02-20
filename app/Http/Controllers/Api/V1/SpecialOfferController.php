<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SpecialOffer;
use Illuminate\Http\JsonResponse;

class SpecialOfferController extends Controller
{
    /**
     * Display a listing of the active special offers.
     */
    public function index(): JsonResponse
    {
        $offers = SpecialOffer::with('restaurant:id,name,logo_url,cover_url')
            ->where('is_active', true)
            ->where(function ($query) {
                $now = now();
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) {
                $now = now();
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->latest()
            ->get();

        return response()->json([
            'data' => $offers,
        ]);
    }
}
