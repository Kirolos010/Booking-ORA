<?php

namespace App\Listeners;

use App\Events\BookingCheckedOut;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PublishCheckoutToBroker
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
 public function handle(BookingCheckedOut $event): void
    {
        Log::info('Publish To Broker', [

            'booking_ref'=>$event->booking->booking_ref,

            'user'=>$event->booking->user_id,

            'amount'=>$event->booking->amount,

            'checked_out_at'=>$event->booking->checked_out_at,

        ]);

        /*
            RabbitMQ

            Kafka

            Redis Stream

            AWS SNS

            NATS

            أي Message Broker مطلوب من الـ Senior
        */
    }
}
