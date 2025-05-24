<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FieldStoreRequest;
use App\Http\Requests\FieldUpdateRequest;
use App\Models\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request)
    {
        $fields = Field::query();
        
        if ($request->filled('workflow_id')) {
            $fields = $fields->where('workflow_id', $request->workflow_id);
        }

        $fields = $fields->get();

        return response()->json($fields);
    }

    public function store(FieldStoreRequest $request)
    {
        $data = $request->validated();
        $data['options'] = explode(',', $request->options);
        $field = Field::query()->create($data);

        return response()->json($field);
    }

    public function update(int $id, FieldUpdateRequest $request)
    {
        $field = Field::query()->findOrFail($id);
        $data = $request->validated();
        $data['options'] = explode(',', $request->options);
        $field->update($data);

        return response()->json($field);
    }

    public function destroy(int $id)
    {
        Field::query()->findOrFail($id)->delete();

        return response()->json([
            'success' => 'Xoá thành công'
        ]);
    }
}
