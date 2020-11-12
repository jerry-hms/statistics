<?php


namespace Hms\Statistics;

use Hms\Statistics\Console\StatisticsCommand;
use Hms\Statistics\Console\StatisticsInitCommand;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{

    /**
     * @var array
     */
    protected $commands = [
        StatisticsCommand::class,
        StatisticsInitCommand::class,
    ];

    /**
     * 服务注册
     */
    public function register()
    {
        // 注册commands
        $this->commands($this->commands);
    }


    public function boot()
    {
        $this->publishes();
    }

}