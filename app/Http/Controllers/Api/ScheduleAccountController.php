<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Auth;
use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
class ScheduleAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::select('email', 'full_name', 'avatar', 'id', 'position')->get();
        if (isset($request->date)) {
            $date = $request->date;
        } else {
            $date = now()->format('Y-m-d');
        }
        $token = "Bearer " . Auth::user()->remember_token;
        $data = Http::withHeaders([
            'Authorization' => $token
        ])->get("https://work.1997.pro.vn/api/schedule?start=" . $date . "&end=" . $date);
        $data = $data->json();
        $data = $data[$date];
        foreach ($accounts as $account) {
            $newData = array_filter($data, function ($item) use ($account) {
                return $item['account_id'] == $account->id;
            });
            $newData = array_values($newData);
            usort($newData, function ($a, $b) {
                return strtotime($a['start']) - strtotime($b['start']);
            });
            $merged = [];
            foreach ($newData as $range) {
                if (empty($merged) || strtotime(end($merged)['end']) < strtotime($range['start'])) {
                    $merged[] = $range;
                } else {
                    // Hợp nhất khoảng thời gian bị trùng
                    $merged[count($merged) - 1]['end'] = max(end($merged)['end'], $range['end']);
                }
            }
            $hoursWork = 0;
            $innerStart1 = Carbon::parse($date . " 08:30:00");
            $innerEnd1 = Carbon::parse($date . " 12:00:00");
            $innerStart2 = Carbon::parse($date . " 13:30:00");
            $innerEnd2 = Carbon::parse($date . " 17:30:00");
            foreach ($merged as $range) {
                if ($innerStart1->greaterThanOrEqualTo(Carbon::parse($range['start'])) && $innerEnd1->lessThanOrEqualTo(Carbon::parse($range['end']))) {
                    $hoursWork = $hoursWork + number_format(3.5, 3);
                } else {
                    $validStart = max($innerStart1, Carbon::parse($range['start']));
                    $validEnd = min($innerEnd1, $range['end']);
                    if ($validStart->lessThan($validEnd)) {
                        $validHours = $validStart->floatDiffInHours($validEnd, true);
                        $hoursWork += number_format($validHours, 3);
                    }
                }

                if ($innerStart2->greaterThanOrEqualTo(Carbon::parse($range['start'])) && $innerEnd2->lessThanOrEqualTo(Carbon::parse($range['end']))) {
                    $hoursWork = $hoursWork + number_format(4, 3);
                } else {
                    $validStart = max($innerStart2, Carbon::parse($range['start']));
                    $validEnd = min($innerEnd2, $range['end']);
                    if ($validStart->lessThan($validEnd)) {
                        $validHours = $validStart->floatDiffInHours($validEnd, true);
                        $hoursWork += number_format($validHours, 3);
                    }
                }
            }
            $account['hours_work'] = number_format($hoursWork, 2);
        }
        return response()->json($accounts);
    }
}
