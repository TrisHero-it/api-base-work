<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProposeStoreRequest;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\DayoffAccount;
use App\Models\Notification;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposeController extends Controller
{
    public function index(Request $request)
    {
        if (isset($request->include)) {
            $proposes = Propose::where('status', 'pending')
                ->get()
                ->count();

            return response()->json($proposes);
        }
        $perPage = $request->per_page ?? 10;
        $proposes = Propose::query()->with(['account', 'propose_category', 'date_holidays', 'approved_by'])
            ->orderByRaw("CASE WHEN status = 'Đang chờ duyệt' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        if (isset($request->status)) {
            $proposes = $proposes->where('status', $request->status);
        }

        if ($request->filled('code')) {
            $proposes = $proposes->where('id', $request->code);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::parse('2004-01-01');
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::parse('3000-01-01');
            $proposes = $proposes->whereBetween('created_at', [$startDate, $endDate]);
        }

        if (isset($request->propose_category_id)) {
            $proposes = $proposes->where('propose_category_id', $request->propose_category_id);
        }

        if (!Auth::user()->isSeniorAdmin()) {
            $proposes = $proposes->where('account_id', Auth::id());
        }

        if ($request->filled('account_id')) {
            $proposes = $proposes->where('account_id', $request->account_id);
        }

        if (isset($request->date)) {
            $date = explode("-", $request->date);
            $year = $date[0];
            $month = $date[1];
            $proposes = $proposes->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }
        $count = [];
        $count['pending'] = Propose::where('status', 'pending')->count();
        $count['approved'] = Propose::where('status', 'approved')->count();
        $count['canceled'] = Propose::where('status', 'canceled')->count();

        $proposes = $proposes->paginate($perPage);
        foreach ($proposes as $propose) {
            $propose['date'] = $propose->date_holidays;
            $propose['account'] = $propose->account;
            $propose['avatar'] = $propose->account->avatar;
            $propose['category_name'] = $propose->propose_category_id == null ? 'Tuỳ chỉnh' : $propose->propose_category->name;
        }

        return response()->json([
            'current_page' => $proposes->currentPage(),
            'data' => $proposes->items(),
            'per_page' => $proposes->perPage(),
            'last_page' => $proposes->lastPage(),
            'from' => $proposes->firstItem(),
            'to' => $proposes->lastItem(),
            'total_pages' => $proposes->lastPage(),
            'total' => $proposes->total(),
            'count' => $count
        ]);
    }

    public function show(int $id)
    {
        $propose = Propose::with(['account', 'date_holidays', 'propose_category', 'approved_by'])
            ->findOrFail($id);
        if ($propose->propose_category->name == 'Đăng ký nghỉ') {
            $numberHoliDay = 0;
            foreach ($propose->date_holidays as $date2) {
                $startDate = Carbon::parse($date2->start_date);
                $endDate = Carbon::parse($date2->end_date);
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Nếu như không phải ngày đầu hay là ngày cuối, thì sẽ +1 ngày công luôn
                    if ($date->format('Y-m-d') != $startDate->format('Y-m-d') && $date->format('Y-m-d') != $endDate->format('Y-m-d')) {
                        $numberHoliDay++;
                    } else {
                        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
                        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
                        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
                        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
                        if ($innerStart1->greaterThanOrEqualTo($startDate) && $innerEnd1->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(3.5 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart1, $startDate);
                            $validEnd = min($innerEnd1, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                        if ($innerStart2->greaterThanOrEqualTo($startDate) && $innerEnd2->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(4 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart2, $startDate);
                            $validEnd = min($innerEnd2, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                    }
                }
            }
            $propose['number_holiday'] = $numberHoliDay;
        }

        return response()->json($propose);
    }

    public function store(ProposeStoreRequest $request)
    {
        $data = $request->except('holiday');
        $data['account_id'] = Auth::id();
        $proposeCategory = ProposeCategory::where('id', $request->propose_category_id)->first();
        if ($request->name == 'Sửa giờ vào ra') {
            $date = explode(' ', $request->start_time)[0];
            $attendance = Attendance::whereDate('checkin', $date)
                ->where('account_id', Auth::id())
                ->first();
            if ($attendance != null) {
                $data['old_check_in'] = $attendance->checkin;
                $data['old_check_out'] = $attendance->checkout;
            } else {
                $data['old_check_in'] = null;
                $data['old_check_out'] = null;
            }
        }
        $numberHoliDay = 0;
        // if ($request->filled('holiday')) {
        //     $request->validate([
        //         'holiday.start_date' => ['required', 'date_format:Y-m-d'],
        //     ], [
        //         'holiday.start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
        //         'holiday.start_date.date_format' => 'Ngày bắt đầu phải có định dạng YYYY-MM-DD.',
        //     ]);
        // }
        if ($proposeCategory->name == "Đăng ký nghỉ") {
            foreach ($request->holiday as $date2) {
                $startDate = Carbon::parse($date2['start_date']);
                $endDate = Carbon::parse($date2['end_date']);
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Nếu như không phải ngày đầu hay là ngày cuối, thì sẽ +1 ngày công luôn
                    if ($date->format('Y-m-d') != $startDate->format('Y-m-d') && $date->format('Y-m-d') != $endDate->format('Y-m-d')) {
                        $numberHoliDay++;
                    } else {
                        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
                        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
                        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
                        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
                        if ($innerStart1->greaterThanOrEqualTo($startDate) && $innerEnd1->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(3.5 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart1, $startDate);
                            $validEnd = min($innerEnd1, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                        if ($innerStart2->greaterThanOrEqualTo($startDate) && $innerEnd2->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(4 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart2, $startDate);
                            $validEnd = min($innerEnd2, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                    }
                }
            }
            if ($request->name == "Nghỉ có hưởng lương") {
                $dayOffAccount = DayoffAccount::where('account_id', Auth::id())->first();
                if ($dayOffAccount->dayoff_count - $numberHoliDay < 0) {
                    return response()->json([
                        'message' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn',
                        'errors' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn'
                    ], status: 401);
                }
            }
        }
        $arr = [];
        $propose = Propose::query()->create($data);
        if (isset($request->holiday)) {
            foreach ($request->holiday as $date) {
                if ($proposeCategory->name == "Đăng ký nghỉ") {
                    $a = [
                        'propose_id' => $propose->id,
                        'number_of_days' => $numberHoliDay
                    ];
                } else {
                    $a = [
                        'propose_id' => $propose->id,
                    ];
                }

                $arr[] = array_merge($a, $date);
            }
        }
        DateHoliday::query()->insert($arr);
        $accounts = Account::where('role_id', 2)
            ->get();
        // foreach ($accounts as $account) {
        //     SendEmail::dispatch($account->email, "Có một yêu cầu $request->name được gửi tới bạn !!");
        // }

        return response()->json($propose);
    }

    public function update(int $id, Request $request)
    {
        if (isset($request->status)) {
            if (!Auth::user()->isSeniorAdmin()) {
                return response()->json([
                    'message' => 'Bạn không có quyền thao tác',
                    'errors' => 'Bạn không có quyền thao tác'
                ], status: 401);
            }
        }
        $propose = Propose::query()->with('propose_category')->findOrFail($id);
        $dayOffAccount = DayoffAccount::where('account_id', $propose->account_id)->first();
        $data = $request->all();
        if (isset($request->status)) {
            $data['approved_by'] = Auth::id();
        }

        if ($request->status == 'approved' && $propose->propose_category->name == 'Nghỉ có hưởng lương') {
            $numberHoliDay = 0;
            foreach ($propose->date_holidays as $date2) {
                $numberHoliDay += $date2->number_of_days;
            }
            if ($dayOffAccount->dayoff_count - $numberHoliDay < 0) {
                return response()->json([
                    'message' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn',
                    'errors' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn'
                ], status: 401);
            }
            $dayOffAccount->update([
                'dayoff_count' => $dayOffAccount->dayoff_count - $numberHoliDay
            ]);
        }
        $propose->update($data);
        if ($request->status == 'approved' && $propose->propose_category->name == 'Sửa giờ vào ra') {
            $date = explode(' ', $propose->start_time)[0];
            $attendance = Attendance::whereDate('checkin', $date)->where('account_id', $propose->account_id)
                ->first();
            if ($attendance != null) {
                $attendance->update([
                    'checkin' => $propose->start_time,
                    'checkout' => $propose->end_time,
                ]);
            } else {
                Attendance::create([
                    'checkin' => $propose->start_time,
                    'checkout' => $propose->end_time,
                    'account_id' => $propose->account_id,
                ]);
            }
        }
        $name = $propose->propose_category->name;
        $status = $propose->status == 'approved' ? 'được chấp nhận' : 'bị từ chối';
        Notification::create([
            'account_id' => $propose->account_id,
            'title' => "$name của bạn đã " . $status,
            'message' => "<strong>$name</strong> của bạn đã " . $status,
            'manager_id' => auth()->id()
        ]);

        return response()->json($propose);
    }

    public function destroy(int $id)
    {
        $propose = Propose::query()->findOrFail($id);
        $propose->delete();

        return response()->json(data: ['success' => 'Xoá thành công']);
    }
}
