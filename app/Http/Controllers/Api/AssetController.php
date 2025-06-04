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
        // đếm số lượng sản phẩm theo status

        $statuses = [
            'using',
            'unused',
            'liquidated',
            'warranty',
            'broken',
            'total'
        ];

        $query = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account']);

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply price range filter
        $this->applyPriceFilter($query, $request);

        // Apply search filter
        $this->applySearchFilter($query, $request);

        $perPage = $request->per_page ?? 8;
        $assets = $query->paginate($perPage)->appends($request->all());


        $statusSummary = Asset::selectRaw('status, COUNT(*) as count')
            ->groupBy('status');
        $this->applyFilters($statusSummary, $request);
        $this->applySearchFilter($statusSummary, $request);
        $statusSummary = $statusSummary->get()
            ->keyBy('status')
            ->toArray();
        $formattedSummary = collect($statuses)->map(function ($status) use ($statusSummary, $assets) {
            if ($status === 'total') {
                return [
                    'total' => $assets->total()
                ];
            } else {
                return [
                    'status' => $status,
                    'count' => $statusSummary[$status]['count'] ?? 0
                ];
            }
        });

        return response()->json([
            'current_page' => $assets->currentPage(),
            'data' => $assets->items(),
            'first_page_url' => $assets->url(1),
            'from' => $assets->firstItem(),
            'last_page' => $assets->lastPage(),
            'last_page_url' => $assets->url($assets->lastPage()),
            'links' => $assets->links(),
            'next_page_url' => $assets->nextPageUrl(),
            'path' => $assets->path(),
            'per_page' => $assets->perPage(),
            'prev_page_url' => $assets->previousPageUrl(),
            'to' => $assets->lastItem(),
            'total' => $assets->total(),
            'total_status' => $formattedSummary,  // Thêm total_status vào response
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        $filters = [
            'brand_id' => 'brand_id',
            'account_id' => 'account_id',
            'category_id' => 'asset_category_id',
            'status' => 'status',
        ];

        foreach ($filters as $requestKey => $columnName) {
            if ($request->filled($requestKey)) {
                $query->where($columnName, $request->input($requestKey));
            }
        }
    }

    private function applyPriceFilter($query, Request $request)
    {
        if ($request->filled('start_price') || $request->filled('end_price')) {
            $startPrice = $request->start_price ?? 0;
            $endPrice = $request->end_price ?? PHP_FLOAT_MAX;
            $query->whereBetween('price', [$startPrice, $endPrice]);
        }
    }

    private function applySearchFilter($query, Request $request)
    {
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('code', 'like', "%{$searchTerm}%")
                    ->orWhere('name', 'like', "%{$searchTerm}%");
            });
        }
    }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->safe()->except('asset_category_id');
        $data['asset_category_id'] = $request->asset_category_id;
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
        $asset = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account'])
            ->findOrFail($id);

        $data = $this->prepareUpdateData($request, $asset);

        // Create history record
        HistoryAsset::create([
            'asset_id' => $asset->id,
            'status' => 'updated',
            'account_id' => auth()->user()->id,
            'date_time' => now() // Adding date_time to be consistent with store method
        ]);

        $asset->update($data);

        return response()->json($asset);
    }

    private function prepareUpdateData(UpdateAssetRequest $request, Asset $asset): array
    {
        $data = $request->validated();

        if ($request->filled('status')) {
            $data = $this->handleStatusUpdate($data, $asset);
        }

        if ($request->filled('asset_category_id')) {
            $data['asset_category_id'] = $request->asset_category_id;
        }

        return $data;
    }

    private function handleStatusUpdate(array $data, Asset $asset): array
    {
        if ($data['status'] === 'using' && $asset->status !== 'using') {
            $data['start_date'] = now();
        }
        if ($data['status'] === 'unused') {
            $data['start_date'] = null;
            $data['account_id'] = null;
        }

        return $data;
    }

    public function show(int $id)
    {
        $asset = Asset::with(['buyer', 'seller', 'assetCategory', 'account', 'brand', 'historyAssets.account'])
            ->findOrFail($id);

        return response()->json($asset);
    }

    public function destroy(int $id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted successfully']);
    }
}
