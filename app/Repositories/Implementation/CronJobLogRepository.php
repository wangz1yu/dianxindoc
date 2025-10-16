<?php

namespace App\Repositories\Implementation;

use App\Models\CronJobLogs;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Carbon\Carbon;

class CronJobLogRepository extends BaseRepository implements CronJobLogRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    public static function model()
    {
        return CronJobLogs::class;
    }

    public function createCronJobLog($attributes)
    {
        $endTime = Carbon::now();
        $attributes['executionTime'] = abs($endTime->diffInSeconds($attributes['startedAt']) ?? 0);
        $attributes['endedAt'] = $endTime;
        return $this->create($attributes);
    }

    public function getAllCronJobLogs($attributes)
    {
        $query = CronJobLogs::select(['cronJobLogs.*']);

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0] ?? 'startedAt';
        $direction = $orderByArray[1] ?? 'asc';

        $query = $query->orderBy($orderBy, $direction);

        if ($attributes->jobName) {
            $query = $query->where('cronJobLogs.jobName',  'like', '%' . urldecode($attributes->jobName) . '%');
        }
        if ($attributes->output) {
            $query = $query->where('cronJobLogs.output',  'like', '%' . $attributes->output . '%');
        }
        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();
        return $results;
    }

    public function getAllCronJobLogCount($attributes)
    {
        $query = CronJobLogs::query();

        if ($attributes->jobName) {
            $query = $query->where('cronJobLogs.jobName',  'like', '%' . urldecode($attributes->jobName) . '%');
        }
        if ($attributes->output) {
            $query = $query->where('cronJobLogs.output',  'like', '%' . $attributes->output . '%');
        }

        $count = $query->count();
        return $count;
    }
}
