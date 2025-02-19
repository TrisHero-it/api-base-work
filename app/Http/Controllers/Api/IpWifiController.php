<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ipWifi;
use Auth;
use Illuminate\Http\Request;

class IpWifiController extends Controller
{
    public function index()
    {
        $ipWifi = ipWifi::all();

        return response()->json($ipWifi);
    }

    public function store(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $ip = request()->ip();
            $ipWifi = ipWifi::where('ip', $ip)->first();
            if (!$ipWifi) {
                $ipWifi = ipWifi::create([
                    'ip' => $ip
                ]);
            }
        }

        return response()->json($ipWifi);
    }

    public function destroy($id)
    {
        $ipWifi = ipWifi::find($id);
        $ipWifi->delete();

        return response()->json(['success' => 'Xóa thành công']);
    }

    public function getIpWifi()
    {
        $ip = request()->ip();

        return response()->json($ip);
    }
}
