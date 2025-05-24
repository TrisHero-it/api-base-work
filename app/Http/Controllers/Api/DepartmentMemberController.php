<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentMemberController extends Controller
{
    public function index(Request $request)
    {
        $department = Department::with('members.jobPositionActive')->findOrFail($request->id);

        $department->members->map(function ($member) {
            if ($member->jobPositionActive != null) {
                $member->position = $member->jobPositionActive->name;
            }
            return $member;
        });

        return response()->json($department);
    }
}
