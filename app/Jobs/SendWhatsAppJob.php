<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Citizen;
use GuzzleHttp\Client;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $citizen;
    /**
     * Create a new job instance.
     */
     public function __construct(Citizen $citizen)
    {
        $this->citizen = $citizen;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = new Client(['timeout' => 10]);
        $endpoint = env('ULTRAMSG_ENDPOINT', 'https://api.ultramsg.com/instance152385/messages/chat');
        $payload = [
            'token' => env('ULTRAMSG_API_TOKEN'),
            'to' => $this->citizen->mobile,
            'body' => "Verify your Account. Your verification code: {$this->citizen->verification_code}. \nThis code expires in 5 minutes."
        ];
        $client->post($endpoint, ['json' => $payload]);
    }
}
