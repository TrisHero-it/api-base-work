<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\View;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    public function index()
    {
        $views = View::all();
        return response()->json($views);
    }

    public function store(Request $request)
    {
        $view = View::create([
            'name' => $request->name,
            'field_name' => $request->field_name,
        ]);
        return response()->json($view);
    }
}
