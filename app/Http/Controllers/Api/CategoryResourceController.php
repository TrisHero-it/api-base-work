<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryResourceController extends Controller
{
    public function index()
    {
        $categoryResources = CategoryResource::with([
            'resources' => function ($query) {
                $query->whereHas('members', function ($q) {
                   if(!Auth::user()->isSeniorAdmin()){
                    $q->where('account_id', Auth::id());
                   }
                })->with('members', 'receivers');
            }
        ])->get();

        return response()->json($categoryResources);
    }

    public function store(Request $request)
    {
        $categoryResource = CategoryResource::create($request->all());

        return response()->json($categoryResource);
    }

    public function update(Request $request, $id)
    {
        $categoryResource = CategoryResource::findOrFail($id);
        $categoryResource->update($request->all());

        return response()->json($categoryResource);
    }

    public function destroy($id)
    {
        $categoryResource = CategoryResource::findOrFail($id);
        $categoryResource->delete();

        return response()->json(['success' => 'Xoá thành công danh mục tài nguyên']);
    }
}
