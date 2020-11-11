<?php

namespace Hms\Statistics\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StatisticsInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化统计表，写入0数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**1
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::channel('statistics_sync_start')->info('开始执行init');
        $day = Carbon::today()->toDateString();
        foreach ($this->initSyncNamespace() as $namespace) {
            try {
                $class = new $namespace();
                $model = $class->where([$class->statisticsDefaultConditionFields=>$day])->first();
                if(empty($model)){
                    $class->{$class->statisticsDefaultConditionFields} = $day;
                    foreach ($class->incrementFields as $field){
                        $class->$field = 0;
                    }
                    $class->save();
                }
                Log::channel('statistics_sync_success')->info('当前模型:' . $namespace . '初始化成功');
            }catch (\Exception $e){
                Log::channel('statistics_sync_error')->info('当前模型:' . $namespace . '[初始化],错误信息:'. $e->getMessage());
            }
        }
    }

    /**
     * 初始化同步工具
     *
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function initSyncNamespace()
    {
        return config('statistics.namespaces') ?:[];
    }

}
