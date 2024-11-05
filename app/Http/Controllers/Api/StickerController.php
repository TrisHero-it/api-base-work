<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sticker;
use Illuminate\Http\Request;

class StickerController extends Controller
{
    public function index()
    {
        $stickers = Sticker::all();

        return response()->json($stickers);
    }

    public function store(Request $request)
    {
        try {
            Sticker::query()->create($request->all());
            return response()->json(['success'=> 'Thêm thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $sticker = Sticker::query()->findOrFail($id);
            $sticker->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }


    }
}
