<?php


namespace Hms\Statistics\Models;


use Jenssegers\Mongodb\Eloquent\Model;

class StatisticsLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'statistics_logs';

    protected $primaryKey = '_id';

    protected $fillable = [
        'module', 'unique_condition',
    ];

}