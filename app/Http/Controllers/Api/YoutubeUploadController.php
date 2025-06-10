<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YoutubeUpLoad;
use Illuminate\Http\Request;

class YoutubeUploadController extends Controller
{
    public function index()
    {
        $youtubeUploads = YoutubeUpload::query();
        if (request()->filled('status')) {
            $youtubeUploads = $youtubeUploads->where('status', 'pending')->first();
        } else {
            $youtubeUploads = $youtubeUploads->get();
        }
        return response()->json($youtubeUploads);
    }

    public function store(Request $request)
    {
        $youtubeUpload = YoutubeUpload::create($request->all());
        return response()->json($youtubeUpload);
    }

    public function update(Request $request, $id)
    {
        $youtubeUpload = YoutubeUpload::find($id);
        $youtubeUpload->update($request->all());
        return response()->json($youtubeUpload);
    }
}
