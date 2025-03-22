<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $assetCategories = AssetCategory::all();

        return response()->json($assetCategories);
    }

    public function store(Request $request)
    {
        $assetCategory = AssetCategory::create($request->all());
        return response()->json($assetCategory);
    }
}
