<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
     public function store(Request $request)
     {
         $role = Role::query()->create($request->all());

         return response()->json($role);
     }

     public function index()
     {
         $roles = Role::query()->get();

         return response()->json($roles);
     }
}
