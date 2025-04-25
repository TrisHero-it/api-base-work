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

    public function update(Request $request, $id)
    {
        $assetCategory = AssetCategory::find($id);
        $assetCategory->update($request->all());

        return response()->json($assetCategory);
    }

    public function destroy($id)
    {
        $assetCategory = AssetCategory::find($id);
        $assetCategory->delete();

        return response()->json($assetCategory);
    }
}
