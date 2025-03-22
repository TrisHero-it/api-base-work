<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class   AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with(['buyer', 'seller', 'assetCategory', 'account'])->paginate(10);
        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $asset = Asset::create($data);

        return response()->json($asset);
    }
}
