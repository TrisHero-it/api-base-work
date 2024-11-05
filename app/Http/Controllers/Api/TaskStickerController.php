<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StickerTask;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;

class TaskStickerController extends Controller
{
    public function index()
    {
        $stickers = StickerTask::query()->get();

        return response()->json($stickers);
    }

    public function store(Request $request)
    {
        try {
            $sticker = StickerTask::query()->create($request->all());

            return response()->json([
                'success' => 'Thêm thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
               'error' => 'Đã xảy ra lỗi'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sticker = StickerTask::query()->findOrFail($id);
            $sticker->update($request->all());
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $sticker = StickerTask::query()->findOrFail($id);
            $sticker->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ]);
        }
    }

    public function show($id) {
        $sticker = StickerTask::query()->findOrFail($id);
        return response()->json($sticker);
    }
}
