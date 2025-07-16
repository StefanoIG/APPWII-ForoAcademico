<?php

namespace App\Listeners;

use App\Events\BestAnswerMarked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateReputationForBestAnswer
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
    public function handle(BestAnswerMarked $event): void
    {
        $answer = $event->answer;
        $user = $answer->user;
        
        // Incrementar reputación del autor de la respuesta por +10
        $user->increment('reputacion', 10);
    }
}
