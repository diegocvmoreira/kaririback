<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')->orderBy('sort_order')->get();
        return response()->json($categories);
    }

    public function show(Category $category)
    {
        if ($category->status !== 'active') {
            return response()->json(['message' => 'Categoria não encontrada.'], 404);
        }

        return response()->json($category);
    }
}
