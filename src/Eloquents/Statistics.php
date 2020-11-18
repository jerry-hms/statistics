<?php


namespace Hms\Statistics\Eloquents;

use App\Services\Statistics\Exceptions\StatisticsException;
use App\Services\Statistics\Models\StatisticsLog;
use Closure;
use Illuminate\Support\Facades\Redis;


trait Statistics
{
    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * 统计维度字段 (需要被该数组中定义的字段均被视为统计维度)
     *
     * @var array
     */
    protected $statisticsConditionFields;


    /**
     * 默认必须的统计维度 日期 Y-m-d
     *
     * @var array
     */
    public $statisticsDefaultConditionFields = 'day';

    /**
     * 去重条件数组
     *
     * @var array
     */
    public $statisticsDistinct;

    /**
     * 自增字段
     *
     * @var array
     */
    public $incrementFields;

    public $statisticsRedis;

    /**
     * 设置redis连接
     */
    public function setStatisticsConnection() {

        if ($this->statisticsRedis) {
            return $this->statisticsRedis;
        }

        $this->statisticsRedis = Redis::connection(config('statistics.connection'));
        return  $this->statisticsRedis;
    }

    /**
     * 初始化统计对象
     *
     * @return static
     */
    public static function initStatistics()
    {
        return new static();
    }

    /**
     * 进行数据统计
     * @param $parameters
     * @param bool $cover
     * @return bool
     * @throws StatisticsException
     */
    public function statistics($parameters, $cover = false)
    {
        if ( empty(array_intersect($this->statisticsConditionFields, array_keys($parameters)))) {
            throw new StatisticsException('At least one condition must be specified when using statistics');
        }

        if ($cover == false &&
            $this->statisticsConditionFields &&
            $model = $this->where($this->buildCondition($parameters))->first()) {

            return $model->fill(
                $this->incrementParameters($model->toArray(), $parameters)
            )->save();

        } else {

            return ! empty($this->create($parameters));
        }
    }

    /**
     * 生成查询条件
     *
     * @param $parameters
     * @return array
     */
    public function buildCondition($parameters) {
        $conditions = [];
        foreach ($this->statisticsConditionFields as $field) {

            if (isset($parameters[$field])) {
                $conditions[$field] = $parameters[$field];
            } else {
                $conditions[$field] =  null;
            }

        }

        return $conditions;
    }

    /**
     * 将数据进行自增
     *
     * @param array $originAttributes
     * @param array $attributes
     * @return array
     */
    public function incrementParameters(array $originAttributes, array $attributes)
    {
        $totalAttributes = $this->filterAttributes(array_merge($attributes, $originAttributes));

        foreach ($originAttributes as $field => $value) {

            if (! in_array($field, $this->statisticsConditionFields) &&
                isset($attributes[$field]) &&
                in_array($field, $this->fillable) &&
                in_array($field, $this->incrementFields)
            ) {
                $totalAttributes[$field] += $attributes[$field];
            }
        }
        return $totalAttributes;
    }


    /**
     * 将统计数据缓存到redis
     *
     * @param array $attributes
     * @param Closure|null $alterClosure
     * @return bool|void
     * @throws StatisticsException
     */
    public function cache(array $attributes, Closure $alterClosure = null)
    {
        if (! $this->collection) {
            throw new StatisticsException('this collection not defined!');
        }

        if (! $this->beforeFunc($alterClosure)) {
            return;
        }

        $this->statisticsRedis = self::setStatisticsConnection();

        foreach ($this->getCacheFields($attributes) as $cacheField) {

            $key = $this->cacheKey ?: $this->getCacheKey();
            if (! $this->statisticsRedis->hexists($key, $cacheField)) {

                $this->statisticsRedis->hset($key, $cacheField, json_encode(
                    $this->filterAttributes($attributes)
                ));

            } else {

                $this->statisticsRedis->hset($key, $cacheField, json_encode(
                    $this->incrementParameters(
                        json_decode($this->statisticsRedis->hget($key, $cacheField), 1),
                        $attributes)
                ));
            }
        }

        $this->alterFunc($alterClosure);

        return true;
    }


    /**
     * 存储前的操作
     *
     * @param Closure|null $alterClosure
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|mixed|object|null
     * @throws StatisticsException
     */
    protected function beforeFunc(Closure $alterClosure = null)
    {
        $distinct = null;
        if (is_array($this->statisticsDistinct)) {

            $distinct = StatisticsLog::query()->where([
                    'module'            => get_class($this),
                    'unique_condition'  => $this->getLogConditions(),
                ])->first();
        }

        elseif ($this->statisticsDistinct instanceof Closure) {

            if (! $alterClosure) {
                throw new StatisticsException('When the setDistinct() function parameter is a closure function, the parameter 2 of the cache() function must be specified');
            }

            $distinct = call_user_func($this->statisticsDistinct);
        }

        return empty($distinct);
    }

    /**
     * 存储后的操作
     *
     * @param Closure|null $alterClosure
     * @return mixed|null
     */
    protected function alterFunc(Closure $alterClosure = null)
    {
        // 如果设置了去重条件,默认在存储后进行记录写入
        if (is_array($this->statisticsDistinct)) {
            return StatisticsLog::create([
                'module'            => get_class($this),
                'unique_condition'  => $this->getLogConditions()
            ]);
        }

        // 判断是否实现存储后的回调，如果实现则进行调用
        else {
            return $alterClosure instanceof Closure ? $alterClosure() : null;
        }
    }

    /**
     * 获取日志的查询条件
     *
     * @return false|string
     */
    protected function getLogConditions()
    {
        ksort($this->statisticsDistinct);

        return json_encode($this->statisticsDistinct);
    }

    /**
     * 去重条件设置
     *
     * @param $conditions
     * @return $this
     */
    public function setDistinct($conditions)
    {
        $this->statisticsDistinct = $conditions;

        return $this;
    }


    /**
     * 过滤缓存数据
     *
     * @param $attributes
     * @return array
     */
    protected function filterAttributes($attributes) {

        return collect($attributes)->filter(function ($value, $key) {
            return ! in_array($key, $this->statisticsConditionFields) && in_array($key, $this->fillable);
        })->all();
    }

    /**
     * 获取默认缓存key
     *
     * @return string
     */
    public function getCacheKey()
    {
        $this->cacheKey = $this->getConnectionName() . '_' . $this->collection;

        return $this->cacheKey;
    }

    /**
     * 获取缓存hash的所有field字段名
     *
     * @param array $attribute
     * @return array
     */
    public function getCacheFields(array $attribute)
    {
        sort($this->statisticsConditionFields);

        return depth_picker($this->statisticsConditionFields, $attribute);
    }


}