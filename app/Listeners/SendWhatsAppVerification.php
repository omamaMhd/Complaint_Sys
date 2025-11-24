<?php

namespace App\Listeners;

use App\Events\CitizenVerificationCodeGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SendWhatsAppJob;

class SendWhatsAppVerification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CitizenVerificationCodeGenerated $event)
    {
        // dispatch job to queue (faster response)
        SendWhatsAppJob::dispatch($event->citizen);
    }
}
