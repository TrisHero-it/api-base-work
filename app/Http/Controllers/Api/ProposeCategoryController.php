<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProposeCategory;
use Illuminate\Http\Request;

class ProposeCategoryController extends Controller
{
    public function index()
    {
        $categories = ProposeCategory::whereNotIn('name', ['Cập nhật thông tin cá nhân'])
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $category = ProposeCategory::query()->create($data);

        return response()->json($category);
    }

    public function update(int $id, Request $request)
    {
        $data = $request->all();
        $category = ProposeCategory::query()->findOrFail($id);
        $category->update($data);

        return response()->json($category);
    }

    public function destroy(int $id)
    {
        $category = ProposeCategory::query()->findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => 'Xoá thành công'
        ]);
    }
}
