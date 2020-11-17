<?php


namespace Hms\Statistics\Eloquents;


use Illuminate\Support\Facades\Redis;

/**
 * Trait Cache
 * @package Hms\Statistics\Eloquents
 */
trait Cache
{

    protected $conditions = [];

    protected $statisticsRedis;

    /**
     * 映射哈希字段
     *
     * @param mixed ...$args
     * @return $this
     */
    public function mapHashFields(...$args)
    {
        $this->conditions[$args[0]] = implode(':', $args);

        return $this;
    }

    /**
     * 获取缓存
     *
     * @return array
     */
    public function getCache()
    {
        $this->statisticsRedis = $this->setStatisticsConnection();

        if (! $this->statisticsRedis->hexists($this->getCacheKey(), $this->resolveCondition())) {
            return [];
        }

        $collection = $this->statisticsRedis->hget($this->getCacheKey(), $this->resolveCondition());

        return $this->resolveCollect($collection, $this->resolveCondition());
    }

    /**
     * 解析查询条件
     *
     * @return string
     */
    protected function resolveCondition() {
        ksort($this->conditions);

        return implode(':', array_values($this->conditions));
    }

    /**
     * 解析hash数据
     *
     * @param $collect
     * @param $fields
     * @return array
     */
    public function resolveCollect($collect, $fields) {

        return array_merge(splice_arr($fields), json_decode($collect, 1));
    }


}