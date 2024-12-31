<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DayScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::query()->where('go_to_work', false)->get();

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $a = Schedule::query()->latest('id')->first();
        if (!empty($a)) {
            $date = Carbon::parse($a->day_of_week);
            $startDate = $date->addMonthNoOverflow()->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        }else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        $schedule = Schedule::query()->create([
                'day_of_week' => $request->day_of_week ,
                'go_to_work' => $request->go_to_work,
                'description' => $request->description,
            ]);

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
