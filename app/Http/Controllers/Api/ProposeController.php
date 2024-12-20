<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Models\Account;
use App\Models\Propose;
use Illuminate\Http\Request;

class ProposeController extends Controller
{
    public function index()
    {
        $proposes = Propose::query()->orderBy('created_at', 'desc')->get();
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
        $data = $request->all();
        $a = $request->header('authorization');
        $a = explode(' ', $a);
        $a = Account::query()->where('remember_token', $a[1])->first();
        $data['account_id'] = $a->id;
        $propose = Propose::query()->create($data);
        $accounts = Account::query()->where('role_id', 2)->get();
        foreach ($accounts as $account) {
            SendEmail::dispatch([
                'email' => $accounts->email,
                'body' => "<a href='https://work.1997.pro.vn/request'> Có 1 dề xuất mới từ <strong>$a->full_name</strong> </a>"
            ]);
        }
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
