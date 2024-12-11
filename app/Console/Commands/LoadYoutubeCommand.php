<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class LoadYoutubeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-youtube-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load lại số like, comment, views của youtube';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::query()->where('link_youtube', '!=', null)->get();
        foreach ($tasks as $task) {
            $videoId = $task->link_youtube; // Thay VIDEO_ID bằng ID của video YouTube
            $apiKey = 'AIzaSyCHenqeRKYnGVIJoyETsCgXba4sQAuHGtA'; // Thay YOUR_API_KEY bằng API key của bạn

            $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$apiKey}&part=snippet,contentDetails,statistics";

            $response = file_get_contents($url);
            $data = json_decode($response, true);
            $task->update([
                'view_count' => $data['items'][0]['statistics']['viewCount'],
                'like_count' => $data['items'][0]['statistics']['likeCount'],
                'comment_count' => $data['items'][0]['statistics']['commentCount'],
            ]);
        }
    }
}
