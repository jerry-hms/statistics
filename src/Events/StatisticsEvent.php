<?php

namespace Hms\Statistics\Events;

use App\Models\Statistics\BaseMongo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Jenssegers\Mongodb\Eloquent\Model;

class StatisticsEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;

    public $args;

    public $condition;

    public $conditionMap;

    /**
     * Create a new event instance.
     *
     * @param Model $model
     * @param array $args
     * @param mixed $condition
     * @param array $conditionMap
     */
    public function __construct(Model $model, array $args, $condition = null, $conditionMap = null)
    {
        $this->model = $model;

        $this->args = $args;

        $this->condition = $condition;

        $this->conditionMap = $conditionMap;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
