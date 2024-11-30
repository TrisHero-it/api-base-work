<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StickerTask;
use App\Models\Task;
use Illuminate\Http\Request;

class TagValueController extends Controller
{
    public function store(Request $request) {
        $data = [];
        $task_id = Task::query()->where('code', $request->task_id)->first()->id;
        $data['sticker_id'] = $request->tag_id;
        $tag = [];
        foreach ($data['sticker_id'] as $tagId) {
            $tag[] = StickerTask::query()->create([
                'task_id' => $task_id,
                'sticker_id' => $tagId
            ]);
        }
        return [$tag];
    }

    public function update(int $id, Request $request) {
        $arrTag = $request->tag_id;
        $task = Task::query()->where('code', $id)->first();
        StickerTask::query()->where('task_id', $task->id)->delete();
        $tag = [];
        foreach ($arrTag as $tagId) {
            $tag[] = StickerTask::query()->create([
                'task_id' => $task->id,
                'sticker_id' => $tagId
            ]);
        }
        return $tag;
    }

}
