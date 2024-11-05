<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\Request;

class EmojiController extends Controller
{
    public function index(int $id)
    {
        $emojis = Like::query()->where('comment_id', $id)->get();

        return response()->json($emojis);
    }

    public function store(Request $request)
    {
        try {
            Like::query()->create($request->all());
            return response()->json(['success' => 'Thêm thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            Like::query()->findOrFail($id)->delete();
            return response()->json(['success' => 'Xoá thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $emoji = Like::query()->findOrFail($id);
            $emoji->update($request->all());
            return response()->json(['success'=>'Sửa thành công']);
        }catch (\Exception $exception){
            return response()->json(['error'=>'Đã xảy ra lỗi'], 500);
        }
    }
}
