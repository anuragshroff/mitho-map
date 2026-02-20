<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpecialOfferRequest;
use App\Http\Requests\UpdateSpecialOfferRequest;
use App\Models\Restaurant;
use App\Models\SpecialOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSpecialOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $offers = SpecialOffer::with('restaurant:id,name')
            ->orderByDesc('created_at')
            ->paginate(15);

        $restaurants = Restaurant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('admin/special-offers', [
            'offers' => $offers,
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSpecialOfferRequest $request): RedirectResponse
    {
        SpecialOffer::create($request->validated());

        return back()->with('success', 'Special offer created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpecialOfferRequest $request, SpecialOffer $specialOffer): RedirectResponse
    {
        $specialOffer->update($request->validated());

        return back()->with('success', 'Special offer updated successfully.');
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, SpecialOffer $specialOffer): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $specialOffer->update($validated);

        return back()->with('success', 'Special offer status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SpecialOffer $specialOffer): RedirectResponse
    {
        $specialOffer->delete();

        return back()->with('success', 'Special offer deleted successfully.');
    }
}
