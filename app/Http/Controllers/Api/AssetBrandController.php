<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetBrand;
use Illuminate\Http\Request;

class AssetBrandController extends Controller
{
    public function index(Request $request)
    {
        $assetBrands = AssetBrand::all();

        return response()->json($assetBrands);
    }

    public function store(Request $request)
    {
        $assetBrand = AssetBrand::create($request->all());

        return response()->json($assetBrand);
    }

    public function update(Request $request, $id)
    {
        $assetBrand = AssetBrand::find($id);
        $assetBrand->update($request->all());

        return response()->json($assetBrand);
    }

    public function destroy($id)
    {
        $assetBrand = AssetBrand::find($id);
        $assetBrand->delete();

        return response()->json($assetBrand);
    }
}
