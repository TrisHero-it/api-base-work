<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\HistoryAsset;
use Illuminate\Http\Request;

class   AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account']);
        $page = $request->per_page ?? 8;
        $filters = [
            'brand_id',
            'account_id',
            'category_id',
            'status',
        ];
        foreach ($filters as $filter) {
            if ($request->filled($filter)) {
                if ($filter == 'category_id') {
                    $assets->where('asset_category_id', $request->category_id);
                } else {
                    $assets->where($filter, $request->$filter);
                }
            }
        }
        if ($request->filled('start_price') || $request->filled('end_price')) {
            $start_price = $request->start_price ?? 0;
            $end_price = $request->end_price ?? 999999999999999999;
            $assets->whereBetween('price', [$start_price, $end_price]);
        }
        if ($request->filled('search')) {
            $assets->where('code', 'like', '%' . $request->search . '%');
            $assets->orWhere('name', 'like', '%' . $request->search . '%');
        }

        $assets = $assets->paginate($page)
            ->appends($request->all());
        return response()->json($assets);
    }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->safe()->except('category_id');
        $data['asset_category_id'] = $request->category_id;
        $asset = Asset::create($data);

        HistoryAsset::create([
            'asset_id' => $asset->id,
            'status' => 'created',
            'date_time' => now(),
            'account_id' => auth()->user()->id
        ]);

        return response()->json($asset);
    }

    public function update(UpdateAssetRequest $request, int $id)
    {
        $asset = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account'])->find($id);
        $data = $request->validated();
        $status = 'updated';
        if ($request->filled('status')) {
            if ($data['status'] == 'using' && $asset->status != 'using') {
                $data['start_date'] = now();
            }
            if ($request->status == 'unused') {
                $data['start_date'] = null;
                $data['account_id'] = null;
            }

            if ($data['status'] != $asset->status) {
                $status = $data['status'];
            }
        }
        if ($request->filled('category_id')) {
            $data['asset_category_id'] = $request->category_id;
        }

        if ($asset == null) {
            return response()->json(['message' => 'Asset not found'], 404);
        }
        HistoryAsset::create([
            'asset_id' => $asset->id,
            'status' => $status,
            'account_id' => auth()->user()->id
        ]);
        $asset->update($data);
        return response()->json($asset);
    }

    public function show(int $id)
    {
        $asset = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account'])
            ->find($id);

        return response()->json($asset);
    }
}
