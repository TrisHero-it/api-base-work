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
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Tạo tên file theo định dạng YYYYMMDD_uniqid_tengoc
            $filename = now()->format('Y-m-d') . '_' . uniqid() . '_' . $image->getClientOriginalName();
            // Lưu file vào thư mục /public/images với tên mới
            $path = $image->storeAs('/public/images', $filename);
            // Lấy URL của file
            $imageUrl = Storage::url($path);

            return response()->json(['urlImage' => $imageUrl]);
        }

        return response()->json(['error' => 'No image uploaded'], 400);
    }
}
