<?php


namespace Hms\Statistics\Console;


use App\Models\Statistics\AppDownloadStatistics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class StatisticsCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'statistics:sync';

    /**
     * @var string
     */
    protected $description = '同步redis中的统计数据到对应表中';

    /**
     * 同步注册表中定义的模型数据到mongodb
     */
    public function handle()
    {
        Log::channel('statistics_sync_success')->info('开始执行sync');

        foreach ($this->initSyncNamespace() as $namespace) {
            try {
                $class = new $namespace();

                if ($classResults = $class->setStatisticsConnection()->hgetall($class->getCacheKey())) {

                    foreach ($classResults as $fields => $value) {

                        $attributes = array_merge(splice_arr($fields), json_decode($value, 1));

                        $class->statistics($attributes);

                        if ($class->delCache) {
                            $class->setStatisticsConnection()->hdel($class->getCacheKey(), $fields);
                        }

                        Log::channel('statistics_sync_success')->info('当前模型:' . $namespace . ',数据:' . $value . ',同步成功');

                    }
                } else {
                    Log::channel('statistics_sync_success')->info('当前模型:' . $namespace .',没有同步数据');
                }
            } catch (\Exception $e) {
                Log::channel('statistics_sync_error')->info(
                    '当前模型:' . $namespace . '[同步失败],错误信息:'. $e->getMessage()
                );
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