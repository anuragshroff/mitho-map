<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserAddressRequest;
use App\Http\Requests\UpdateUserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * Store a newly created address for the user.
     */
    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        // If this is set to default, unset all others
        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($request->validated());

        return response()->json([
            'data' => $address,
            'message' => 'Address created successfully.',
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'data' => $address,
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(UpdateUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($request->boolean('is_default') && ! $address->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'data' => $address,
            'message' => 'Address updated successfully.',
        ]);
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ]);
    }
}
