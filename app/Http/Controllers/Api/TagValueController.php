<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StickerTask;
use App\Models\Task;
use Illuminate\Http\Request;

class TagValueController extends Controller
{
    public function store(Request $request) {
        if (isset($request->tag_id)) {
            $data = [];
            $task_id = Task::query()->findOrFail($request->task_id)->id;
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
        return ['success' => true];
    }

    public function update(int $id, Request $request) {
        if (isset($request->tag_id)) {
            $arrTag = $request->tag_id;
            $task = Task::query()->findOrFail($id);
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

}
