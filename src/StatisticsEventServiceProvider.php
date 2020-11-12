<?php


namespace Hms\Statistics;


use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class StatisticsEventServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     * @var array
     */
    protected $listen = [
        'App\Services\Statistics\Events\StatisticsEvent' => [
            'App\Services\Statistics\Listeners\StatisticsListeners',
        ],
    ];

    /**
     * 事件启动
     */
    public function boot()
    {
        parent::boot();
    }
}