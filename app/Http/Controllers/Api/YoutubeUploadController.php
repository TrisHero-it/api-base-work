<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YoutubeUpLoad;
use Illuminate\Http\Request;

class YoutubeUploadController extends Controller
{
    public function index()
    {
        $youtubeUploads = YoutubeUpload::query()
            ->with('youtubeChannel')
            ->orderBy('created_at', 'desc');
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


    public function destroy($id)
    {
        $youtubeUpload = YoutubeUpload::find($id);
        $youtubeUpload->delete();
        return response()->json(['message' => 'Youtube upload deleted successfully']);
    }

    public function getYoutubeUploadsPending(Request $request)
    {
        $youtubeUploads = YoutubeUpload::where('status', 'pending')
            ->where('youtube_channel_id', $request->category_id);

        if ($request->filled('property')) {
            if ($request->property == 'date' || $request->property == 'time') {
                $youtubeUploads = $youtubeUploads
                    ->select('upload_date')
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
            if ($request->property == 'playlist-length') {
                $youtubeUploads = $youtubeUploads
                    ->select("playlist")
                    ->where('status', 'pending')
                    ->first();

                return response(count($youtubeUploads->playlist), 200)
                    ->header('Content-Type', 'text/plain');
            }

            $youtubeUploads = $youtubeUploads
                ->select($request->property)
                ->first();

            return response($youtubeUploads[$request->property], 200)
                ->header('Content-Type', 'text/plain');
        } else {

            if ($request->filled('status')) {
                $youtubeUploads = $youtubeUploads->first();
                $youtubeUploads->update([
                    'status' => 'success',
                ]);
            }

            if ($request->filled('playlist_id')) {
                $youtubeUploads = $youtubeUploads
                    ->select("playlist")
                    ->first();

                return response($youtubeUploads->playlist[$request->playlist_id], 200)
                    ->header('Content-Type', 'text/plain');
            }
            $youtubeUploads = YoutubeUpload::where('status', 'pending')
                ->with('youtubeChannel')
                ->first();
        }

        return response()->json($youtubeUploads);
    }
}
