<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::when($request->search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })->get();

        return response()->json($resources);
    }

    public function store(Request $request)
    {
        $resource = Resource::create($request->all());

        return response()->json($resource);
    }


    public function update(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        $resource->update($request->all());

        return response()->json($resource);
    }

    public function destroy($id)
    {
        $resource = Resource::findOrFail($id);
        $resource->delete();

        return response()->json(['success' => 'Xoá thành công tài nguyên']);
    }
}
