<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Models\Account;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposeController extends Controller
{
    public function index(Request $request)
    {
        $proposes = Propose::query()->orderBy('created_at', 'desc');
        if (isset($request->status)) {
            $proposes = $proposes->where('status', $request->status);
        }

        $proposes = $proposes->get();

        foreach ($proposes as $propose) {
            $propose['full_name'] = $propose->account->full_name;
            $propose['avatar'] = $propose->account->avatar;
            $propose['category_name'] = $propose->propose_category== null ? 'Tuỳ chỉnh' : $propose->propose_category->name; ;
            unset($propose['account']);
            unset($propose['propose_category']);
            unset($propose['account_id']);
        }

        return response()->json($proposes);
    }

    public function store(Request $request)
    {
        $a = Auth::user();
        $data['account_id'] = $a->id;
        $accounts = Account::query()->where('role_id', 2)->get();
        $category = ProposeCategory::query()->where('id', $data['propose_category_id'])->first();
        $name = $category == null ? 'Tuỳ chọn' : $category->name;
        foreach ($accounts as $account) {
            SendEmail::dispatch([
                'email' => $account->email,
                'body' => "<p style='font-size: 20px'> Có 1 đề xuất mới từ <strong>$a->full_name</strong> ở mục <strong>$name</strong></strong> <br>
                           Xem chi tiết tại <a href='https://work.1997.pro.vn/request'>Đây</a>
                           </p>"
            ]);
        }
        $propose = Propose::query()->create($data);

        return response()->json($propose);
    }

    public function update(int $id, Request $request)
    {
        if (!Auth::user()->isSeniorAdmin()){
            return response()->json([
                'message'=> 'Bạn không có quyền thao tác',
                'errors' => 'Bạn không có quyền thao tác'
            ], 403);
        }
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
