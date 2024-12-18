<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propose;
use Illuminate\Http\Request;

class ProposeController extends Controller
{
    public function index()
    {
        $proposes = Propose::all();
        foreach ($proposes as $propose) {
            $propose['account_name'] = $propose->account->full_name;
            unset($propose['account']);
            unset($propose['account_id']);
        }

        return response()->json($proposes);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $propose = Propose::query()->create($data);

        return response()->json($propose);
    }

    public function update(int $id, Request $request)
    {
        $propose = Propose::query()->findOrFail($id);
        $propose->update($request->all());

        return response()->json($propose);
    }

    public function destroy(int $id)
    {
        $propose = Propose::query()->findOrFail($id);
        $propose->delete();

        return response()->json(['success'=> 'Xoá thành công']);
    }
}
