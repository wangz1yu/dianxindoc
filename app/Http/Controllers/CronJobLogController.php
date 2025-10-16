<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Illuminate\Http\Request;

class CronJobLogController extends Controller
{
    private $cronJobLogRepository;

    public function __construct(CronJobLogRepositoryInterface $cronJobLogRepository)
    {
        $this->cronJobLogRepository = $cronJobLogRepository;
    }


    public function getCronJobLogs(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->cronJobLogRepository->getAllCronJobLogCount($queryString);
        return response()->json($this->cronJobLogRepository->getAllCronJobLogs($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }
}
