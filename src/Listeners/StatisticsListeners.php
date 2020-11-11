<?php

namespace Hms\Statistics\Listeners;

use App\Services\Statistics\Events\StatisticsEvent;
use App\Services\Statistics\Models\StatisticsLog;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StatisticsListeners
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     *
     *
     * @param StatisticsEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(StatisticsEvent $event)
    {
        try {

            $event->model->setDistinct($event->condition)->cache($event->args, $event->conditionMap);

        } catch (\Exception $e) {

            Log::channel('statistics')->info('统计失败:'. $e->getMessage());
        }
    }
}
