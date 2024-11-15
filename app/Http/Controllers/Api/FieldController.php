<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request, int $id)
    {
        $yields = Field::query()->where('model', 'field')->where('workflow_id', $id)->get();

        return response()->json($yields);
    }

    public function store(Request $request)
    {
        try {
            $data =  $request->except('options');
            $data['options'] = explode(',', $request->options);
            $file =  Field::query()->create($data);
            return response()->json(['success' => 'Thêm thành công']);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $yield = Field::query()->findOrFail($id);
            $data =  $request->except('options');
            $data['options'] = explode(',', $request->options);
            $yield->update($data);
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }
}
