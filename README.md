# statistics
基于laravel的数据统计存储扩展包
=====================

# 环境要求

- Laravel >= 5.5
- Jenssegers/mongodb >= 3.3.*

# 安装
```
composer require "hms/statistics:1.0"
```

# 配置
创建配置文件:
```
php artisan vendor:publish --tag=statistics-config
```

# 基本用法
#### 在你的model中定义：
```php
use Hms\Statistics\Eloquents\Statistics;
use Jenssegers\Mongodb\Eloquent\Model;

class Example extends Model
{
    use Statistics;

    public function __construct() 
    {
        parent::__construct();
        
        
        $this->statisticsConditionFields = [
            'day'
        ];
        
        $this->incrementFields = [
            'number'
        ];
    }
}
```
你的统计表中必须包含至少一个条件字段和一个统计字段，并且你需要在`statisticsConditionFields`数组中定义你的条件字段，在`incrementFields`中定义你的统计字段。
#### 存入统计业务表
```php
use App\Example;

$example = new Example();
$example->statistics([
    'day'       => Carbon::now()->toDateString(),
    'number'    => 1
]);
```
如上所示，`statistics`方法会接收两个参数，第一个参数是需要存储的数据数组，该方法会默认将数据按model中定义的规则进行判断是新增或是累加操作，如果你期望数据按定义的条件进行强制覆盖操作则可传入第二个参数。
第二个参数是可选参数默认为`null`，当传入`force`时数据将以覆盖的形式存入collection中，当传入`new`时表示该数据以新增方式存入collection中。

#### 使用redis将数据进行缓存
```php
$example->cache([
    'day'       => Carbon::now()->toDateString(),
    'number'    => 3
]);
```

如果你需要在高并发的业务中进行数据统计，那么强烈推荐你使用此方法存储数据，`cache`方法也会将数据按model中定义的规则用hash表保存在你的`redis`中。
> 如上所说`statistics`、`cache` 方法都会默认将数据按`statisticsConditionFields`定义的字段按 and 语句进行去重操作，如果你不希望你这么做，可以调用[`setDistinct`]("setDistinct")方法自定义你的去重条件。
#### 将redis中存储的数据同步到mongodb
在使用前你需要开启Laravel提供的任务[调度功能](https://learnku.com/docs/laravel/5.8/scheduling/3924)
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
并且在`config/statistics.php`的`namespaces`数组中定义你需要进行同步的 model，如下所示:
```php
'namespaces' => [
    App\Example::class
]
```
然后在`App\Console\Kernel`的 `schedule` 方法中加入`statistics`的 [Artisan 命令](https://learnku.com/docs/laravel/5.8/artisan/3913)
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('statistics:sync')->daily();
}
```
至此`statistics`为你提供了一套非常便利的数据统计
> 注：statistics提供了数据redis的存储与查询方法的封装，但是并未实现在查询mongodb时默认将缓存中的数据读取的功能，此时需要自行将缓存数据读出进行合并操作。

# 其他用法
### setDistinct

