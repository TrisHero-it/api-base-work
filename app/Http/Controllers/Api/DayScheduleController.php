<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Auth;
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
        } else {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
        }
        $schedules = Schedule::query()
            ->whereMonth('day_of_week', $month)
            ->whereYear('day_of_week', $year)
            ->orderBy('day_of_week')
            ->get();
        if (Auth::user()->isSalesMember() && Auth::id() != 25) {
            foreach ($schedules as $schedule) {
                $schedule->go_to_work = true;
            }
        }
        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $a = Schedule::query()->whereRaw('DAYOFWEEK(day_of_week) = 7')->latest('id')->first();
        if (!empty($a)) {
            $offSaturday = $a->go_to_work == true ? true : false;
            $date = Carbon::parse($a->day_of_week);
            $startDate = $date->addMonthNoOverflow()->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } else {
            $offSaturday = false;
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }
        $month = $startDate->month;
        $year = $startDate->year;
        $data = [];
        $numSaturday = 0;
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $goToWork = true;
            $description = null;
            if ($date->isSaturday()) {
                if ($offSaturday == true) {
                    $numSaturday++;
                    $offSaturday = false;
                    $goToWork = false;
                    $description = 'Nghỉ thứ 7';
                } else {
                    $offSaturday = true;
                }
            }
            if ($date->isSunday()) {
                $goToWork = false;
                $description = 'Nghỉ ngày chủ nhật';
            }
            $data[] = [
                'day_of_week' => $date->format('Y-m-d'),
                'go_to_work' => $goToWork,
                'start_at' => new \DateTime($date->format('Y-m-d') . ' 8:30'),
                'end_at' => new \DateTime($date->format('Y-m-d') . ' 17:30'),
                'description' => $description
            ];
        }
        Schedule::query()->insert($data);

        $schedules = Schedule::query()->whereYear('day_of_week', $year)->whereMonth('day_of_week', $month)->orderBy('day_of_week')->get();

        return response()->json($schedules);
    }

    public function update(int $id, Request $request)
    {
        if (isset($request->is_holiday)) {
            Schedule::query()->whereIn('id', $request->is_holiday)->update([
                'go_to_work' => false,
                'description' => $request->description
            ]);
        }

        if (isset($request->start_date)) {
            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $dates = [];
            for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                $dates[] = $date->format('Y-m-d');
            }

            Schedule::where(function ($query) use ($dates) {
                foreach ($dates as $date) {
                    $query->orWhereDate('day_of_week', $date);
                }
            })->update([
                'go_to_work' => false,
                'description' => $request->description
            ]);
        }

        if (isset($request->is_not_holiday)) {
            Schedule::query()->whereIn('id', $request->is_not_holiday)->update([
                'go_to_work' => true,
            ]);
        }

        return response()->json(['success' => 'Thành công']);
    }

    public function destroy(int $id, Request $request)
    {
        $a = $request->date;
        $month = $a[1];
        $year = $a[0];
        $schedule = Schedule::query()
            ->whereYear('day_of_week', $year)
            ->whereMonth('day_of_week', $month)
            ->delete();

        return response()->json($schedule);
    }
}
