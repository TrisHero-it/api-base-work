<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new job instance.
     */
    public function __construct(public $email, $data)
    {
        $this->data = $data;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::send('mails.index', ['data' => $this->data], function ($message) {
            $message->to($this->email)
                ->subject('Thông báo từ hệ thống');
        });
    }
}
