<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $banners = Banner::orderBy('order')->orderByDesc('created_at')->paginate(15);

        return Inertia::render('admin/banners', [
            'banners' => $banners,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBannerRequest $request): RedirectResponse
    {
        Banner::create($request->validated());

        return back()->with('success', 'Banner created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($request->validated());

        return back()->with('success', 'Banner updated successfully.');
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $banner->update($validated);

        return back()->with('success', 'Banner status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }
}
