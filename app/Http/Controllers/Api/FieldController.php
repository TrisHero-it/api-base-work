<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request, int $id)
    {
        $yields = Field::query()->where('workflow_id', $id)->get();

        return response()->json($yields);
    }

    public function store(Request $request)
    {
        try {
            Field::query()->create($request->all());
            return response()->json(['success' => 'Thêm thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $yield = Field::query()->findOrFail($id);
            $yield->update($request->all());
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            Field::query()->findOrFail($id)->delete();
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
