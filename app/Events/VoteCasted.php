<?php

namespace App\Events;

use App\Models\Vote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCasted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vote;
    public $action;

    /**
     * Create a new event instance.
     */
    public function __construct(Vote $vote, string $action = 'created')
    {
        $this->vote = $vote;
        $this->action = $action;
    }
}
