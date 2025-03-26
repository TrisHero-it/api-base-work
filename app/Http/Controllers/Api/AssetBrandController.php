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
}
