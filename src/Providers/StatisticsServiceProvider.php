<?php


namespace Hms\Statistics\Providers;

use App\Services\Statistics\Console\StatisticsCommand;
use App\Services\Statistics\Console\StatisticsInit;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{

    /**
     * @var array
     */
    protected $commands = [
        StatisticsCommand::class,
        StatisticsInit::class,
    ];

    /**
     * 服务注册
     */
    public function register()
    {
        // 注册commands
        $this->commands($this->commands);
    }

}