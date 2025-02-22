<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function store(UploadImageStoreRequest $request)
    {
        try {
            $image = $request->file('image');
            if (!isset($image)) {
                return response()->json(['error' => 'file ảnh không tồn tại'], 500);
            }
            $imageUrl = Storage::put('/public/images', $image);
            $imageUrl = Storage::url($imageUrl);
            return response()->json(['urlImage' => $imageUrl]);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
