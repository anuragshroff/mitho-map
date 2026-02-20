<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount('restaurants')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        if (! $category->is_active) {
            abort(404);
        }

        return response()->json([
            'data' => $category,
        ]);
    }
}
