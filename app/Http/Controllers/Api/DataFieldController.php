<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldTask;
use Illuminate\Http\Request;

class DataFieldController extends Controller
{
    public function index()
    {
        $fields = FieldTask::all();

        return response()->json($fields);
    }

    public function store(Request $request)
    {
        try {
            FieldTask::query()->create($request->all());
            return response()->json(['success' => 'Thêm thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Lỗi tùm lum'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $field = FieldTask::query()->findOrFail($id);
            $field->update($request->all());
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $field = FieldTask::query()->findOrFail($id);
            $field->delete();
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
