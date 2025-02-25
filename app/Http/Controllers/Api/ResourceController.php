<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountResource;
use App\Models\NotificationResource;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::with('accounts')
        ->when($request->search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })->get();

        return response()->json($resources);
    }

    public function store(Request $request)
    {
        $data = $request->except('members', 'receivers');
        $resource = Resource::create($data);
        $members = $request->members;
        $receivers = $request->receivers;
        $newArr = [];
        $newArr = array_map(function ($member) use ($resource) {
            return [
                'resource_id' => $resource->id,
                'account_id' => $member
            ];
        }, $members ?? []);
        $newArrReceivers = array_map(function ($receiver) use ($resource) {
            return [
                'resource_id' => $resource->id,
                'account_id' => $receiver
            ];
        }, $receivers ?? []);
        
        AccountResource::insert($newArr);
        NotificationResource::insert($newArrReceivers);
        $resource['members'] = $resource->accounts;
        $resource['receivers'] = $resource->receivers;
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
