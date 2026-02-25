<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendExpoPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = $this->user->expo_push_token;

        if ($token === null || $token === '') {
            return;
        }

        $payload = [
            'to' => $token,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];

        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', $payload);

            if (! $response->successful()) {
                Log::error("[ExpoPushNotification] Failed to send push to {$token}. Response: ".$response->body());
            }
        } catch (\Exception $e) {
            Log::error("[ExpoPushNotification] Exception while sending push to {$token}: ".$e->getMessage());
        }
    }
}
