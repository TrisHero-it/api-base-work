<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountYoutube;
use Illuminate\Http\Request;
use OTPHP\TOTP;

class YoutubeAccountController extends Controller
{
    public function index(Request $request)
    {
        $youtubeAccounts = AccountYoutube::query();

        foreach ($request->all() as $key => $value) {
            $youtubeAccounts = $youtubeAccounts->where($key, $value);
        }
        $youtubeAccounts = $youtubeAccounts->get();
        foreach ($youtubeAccounts as $youtubeAccount) {
            $token = trim($youtubeAccount->token_2fa);
            $clean = str_replace(' ', '', $token);
            // Tạo TOTP object
            $totp = TOTP::create($clean);
            // Sinh mã OTP hiện tại (30s đổi)
            $code = $totp->now();
            $youtubeAccount->code = $code;
        }
        return response()->json($youtubeAccounts);
    }

    public function store(Request $request)
    {
        $youtubeAccount = AccountYoutube::create($request->all());
        return response()->json($youtubeAccount);
    }

    public function update(Request $request, $id)
    {
        $youtubeAccount = AccountYoutube::find($id);
        $youtubeAccount->update($request->all());
        return response()->json($youtubeAccount);
    }

    public function destroy($id)
    {
        $youtubeAccount = AccountYoutube::find($id);
        $youtubeAccount->delete();
        return response()->json(['message' => 'Youtube account deleted successfully']);
    }

    public function accountReportYoutube(Request $request)
    {
        $youtubeAccount = AccountYoutube::orderBy('index', 'asc')->latest()->first();
        if ($request->property == 'index') {
            $youtubeAccount->update([
                'index' => $youtubeAccount->index + 1,
            ]);
        }
        $token = trim($youtubeAccount->token_2fa);
        $clean = str_replace(' ', '', $token);
        // Tạo TOTP object
        $totp = TOTP::create($clean);
        // Sinh mã OTP hiện tại (30s đổi)
        $code = $totp->now();
        $youtubeAccount->code = $code;

        return response($youtubeAccount[$request->property], 200)
            ->header('Content-Type', 'text/plain');
    }
}
