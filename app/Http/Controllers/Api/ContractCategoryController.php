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

    public function update(Request $request, $id)
    {
        $contractCategory = Category_Contract::find($id);
        $contractCategory->update($request->all());

        return response()->json($contractCategory);
    }


    public function destroy($id)
    {
        $contractCategory = Category_Contract::find($id);
        $contractCategory->delete();

        return response()->json($contractCategory);
    }
}
