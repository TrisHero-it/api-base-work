<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category_Contract;
use Illuminate\Http\Request;

class ContractCategoryController extends Controller
{
    public function index()
    {
        $contractCategories = Category_Contract::all();

        return response()->json($contractCategories);
    }

    public function store(Request $request)
    {
        $contractCategory = Category_Contract::create($request->all());

        return response()->json($contractCategory);
    }
}
