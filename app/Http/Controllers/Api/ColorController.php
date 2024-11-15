<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::query()->select('color')->get();

        return response()->json($colors);
    }

    public function store(Request $request)
    {
        try {
            Color::query()->create($request->all());
            return response()->json(['success'=>'Thêm thành công']);
        }catch (\Exception $exception){
            return response()->json(['error'=>'Đã xảy ra lỗi']);
        }
    }

}
