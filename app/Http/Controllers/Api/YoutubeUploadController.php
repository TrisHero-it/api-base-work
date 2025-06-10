<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YoutubeUpLoad;
use Illuminate\Http\Request;

use function Laravel\Prompts\select;

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

    public function getYoutubeUploadsPending(Request $request)
    {
        if ($request->filled('property')) {

            if ($request->property == 'date' || $request->property == 'time') {
                $youtubeUploads = YoutubeUpload::where('status', 'pending')
                    ->select('upload_date')
                    ->with('youtubeChannel')
                    ->first();
                $a = explode(' ', $youtubeUploads->upload_date);
                $date = $a[0];
                $time = $a[1];
                if ($request->property == 'date') {
                    $date = str_replace('-', '/', $date);
                    $formatted = date('d/m/Y', strtotime(str_replace('/', '-', $date)));
                    return response($formatted, 200)
                        ->header('Content-Type', 'text/plain');
                } else {
                    $time = str_replace(':', '', $time);
                    $hour = substr($time, 0, 2);   // "09"
                    $minute = substr($time, 2, 2); // "26"

                    return response($hour . ':' . $minute, 200)
                        ->header('Content-Type', 'text/plain');
                }
            }

            $youtubeUploads = YoutubeUpload::where('status', 'pending')
                ->select($request->property)
                ->with('youtubeChannel')
                ->first();
            return response($youtubeUploads[$request->property], 200)
                ->header('Content-Type', 'text/plain');
        } else {
            $youtubeUploads = YoutubeUpload::where('status', 'pending')
                ->with('youtubeChannel')
                ->first();
        }

        return response()->json($youtubeUploads);
    }
}
