<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountResource;
use App\Models\NotificationResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::with('members', 'receivers')
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })->get();

        return response()->json($resources);
    }

    public function show($id)
    {
        $resource = Resource::with('members', 'receivers')->findOrFail($id);
        $me = AccountResource::where('resource_id', $id)->where('account_id', Auth::id())->first();
        if ($me == null) {
            return response()->json([
                'message' => 'Bạn không có quyền xem tài nguyên này',
                'errors' => 'Bạn không có quyền xem tài nguyên này'
            ], 403);
        }
        return response()->json($resource);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isAdmin()) {
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
        } else {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này',
                'errors' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }
    }


    public function update(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        $data = $request->except('thumbnail');
        if ($request->filled('thumbnail')) {
            $data['thumbnail'] = $request->thumbnail;
        }
        AccountResource::where('resource_id', $id)->delete();
        if ($request->filled('members')) {
            $members = $request->members;
            $newArr = [];
            $newArr = array_map(function ($member) use ($resource) {
                return [
                    'resource_id' => $resource->id,
                    'account_id' => $member
                ];
            }, $members ?? []);
            AccountResource::insert($newArr);
        }
        $resource->update($data);

        return response()->json($resource);
    }

    public function destroy($id)
    {
        if (Auth::user()->isSeniorAdmin()) {
            $resource = Resource::findOrFail($id);
            $resource->delete();

            return response()->json(['success' => 'Xoá thành công tài nguyên']);
        } else {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này',
                'errors' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }
    }
}
