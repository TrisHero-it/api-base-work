<?php

namespace App\Console\Commands;

use App\Models\Task;
use Carbon\Carbon;
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
        // Tuần này
        $endOfThisWeek = Carbon::now()->endOfWeek()->toDateString();
        // Tuần trước
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek()->toDateString();
        $tasks = Task::query()->where('code_youtube', '!=', null)
            ->where('stage_id', '!=', null)
            ->whereBetween('completed_at', [$startOfLastWeek, $endOfThisWeek])
            ->get();
        foreach ($tasks as $task) {
            if ($task->code_youtube != null) {
                $videoId = $task->code_youtube; // Thay VIDEO_ID bằng ID của video YouTube
                $apiKey = 'AIzaSyCHenqeRKYnGVIJoyETsCgXba4sQAuHGtA'; // Thay YOUR_API_KEY bằng API key của bạn
                $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$apiKey}&part=snippet,contentDetails,statistics";

                $response = file_get_contents($url);
                $data = json_decode($response, true);
                $dateTime = new \DateTime($data['items'][0]['snippet']['publishedAt']);
                $dateTime->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
                $valueData = [
                    'view_count' => $data['items'][0]['statistics']['viewCount'],
                    'like_count' => $data['items'][0]['statistics']['likeCount'],
                    'comment_count' => $data['items'][0]['statistics']['commentCount'],
                    'date_posted' => $dateTime,
                ];
                $task->update($valueData);
            }
        }
    }
}
