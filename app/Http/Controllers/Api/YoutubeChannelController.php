<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddYoutubeChannelRequest;
use App\Models\YoutubeChannel;
use Illuminate\Http\Request;

class YoutubeChannelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('search')) {
            $youtubeChannels = YoutubeChannel::where('name', 'like', '%' . $request->search . '%')->get();
        } else {
            $youtubeChannels = YoutubeChannel::all();
        }
        return response()->json($youtubeChannels);
    }

    public function store(AddYoutubeChannelRequest $request)
    {
        $youtubeChannel = YoutubeChannel::create($request->all());
        return response()->json($youtubeChannel);
    }

    public function show($id)
    {
        $youtubeChannel = YoutubeChannel::find($id);
        return response()->json($youtubeChannel);
    }

    public function update(Request $request, $id)
    {
        $youtubeChannel = YoutubeChannel::find($id);
        $youtubeChannel->update($request->all());
        return response()->json($youtubeChannel);
    }

    public function destroy($id)
    {
        $youtubeChannel = YoutubeChannel::find($id);
        $youtubeChannel->delete();
        return response()->json(['message' => 'Youtube channel deleted successfully']);
    }
}
