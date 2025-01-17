<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sticker;
use Illuminate\Http\Request;

class StickerController extends Controller
{
    public function index(Request $request)
    {
        $stickers = Sticker::query()->where('workflow_id', $request->workflow_id)->get();

        return response()->json($stickers);
    }

    public function update(int $id, Request $request)
    {
        $sticker = Sticker::query()->findOrFail($id);
        $sticker->update($request->all());

        return response()->json($sticker);
    }

    public function store(Request $request)
    {
        $tag =  Sticker::query()->create($request->all());

        return response()->json($tag);
    }

    public function destroy(int $id)
    {
        try {
            $sticker = Sticker::query()->findOrFail($id);
            $sticker->delete();

            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }
}
