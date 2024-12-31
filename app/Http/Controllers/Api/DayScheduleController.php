<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DayScheduleController extends Controller
{
    public function index(Request $request)
    {
        if (isset($request->date)) {
            $a = explode("-", $request->date);
            $month = $a[1];
            $year = $a[0];
        }else {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
        }
        $schedules = Schedule::query()
            ->whereMonth('day_of_week', $month)
            ->whereYear('day_of_week', $year)
            ->orderBy('day_of_week', 'desc')
            ->get();

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $a = Schedule::query()->whereRaw('DAYOFWEEK(day_of_week) = 7')->latest('id')->first();
        if (!empty($a)) {
            $date = Carbon::parse($a->day_of_week);
            $startDate = $date->addMonthNoOverflow()->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        }else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        $data = [];
        $numSaturday = 0;
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            if ($date->isSaturday()) {
                $numSaturday++;
            }
            $goToWork = true;
            $description = null;
            if ($date->isSunday()) {
                $goToWork = false;
                $description = 'Nghỉ ngày chủ nhật';
            }
            $data[]  = [
                'day_of_week' => $date->format('Y-m-d'),
                'go_to_work' => $goToWork,
                'description' => $description
            ];
        }


        $schedule = Schedule::query()->insert($data);
            return response()->json($schedule);
    }

    public function update(int $id, Request $request)
    {
        $schedule = Schedule::query()->findOrFail($id);
        $schedule->update([
            'go_to_work' => $request->go_to_work,
            'description' => $request->description
        ]);

        return response()->json($schedule);
    }

    public function destroy(int $id)
    {
        $schedule = Schedule::query()->findOrFail($id);
        $schedule->delete();
    }
}
