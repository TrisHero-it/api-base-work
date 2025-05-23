<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentMemberController extends Controller
{
    public function index(Request $request)
    {
        $department = Department::with('members')->findOrFail($request->id);
        return response()->json($department);
    }
}
