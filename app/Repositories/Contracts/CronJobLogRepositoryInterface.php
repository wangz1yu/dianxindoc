<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface CronJobLogRepositoryInterface extends BaseRepositoryInterface
{
    public function createCronJobLog($attributes);
    public function getAllCronJobLogs($attributes);
    public function getAllCronJobLogCount($attributes);
}
