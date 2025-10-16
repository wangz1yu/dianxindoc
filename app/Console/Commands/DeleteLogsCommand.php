<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\CompanyProfileRepositoryInterface;
use Illuminate\Console\Command;
use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Carbon\Carbon;


class DeleteLogsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:deleteLogs';

    /**
     * @var string
     */
    protected $description = 'Delete Email, Audit and Cron Job Logs.';

    /**
     * @var CompanyProfileRepositoryInterface
     */
    private $companyProfileRepository;
    private $cronJobLogRepository;


    /**
     * @param CompanyProfileRepositoryInterface $link
     */
    public function __construct(
        CompanyProfileRepositoryInterface $companyProfileRepository,
        CronJobLogRepositoryInterface $cronJobLogRepository
    ) {
        parent::__construct();
        $this->companyProfileRepository = $companyProfileRepository;
        $this->cronJobLogRepository = $cronJobLogRepository;
    }

    public function handle()
    {
        $cronJobLog = [
            'jobName' => 'Delete Email, Audit and Cron Job Logs.',
            'startedAt' => Carbon::now(),
        ];

        try {
            $this->companyProfileRepository->deleteLogs();
            $this->info('Delete Email, Audit and Cron Job Logs...');
            $cronJobLog['status'] = 'success';
        } catch (\Exception $e) {
            $cronJobLog['status'] = 'failed';
            $cronJobLog['output'] = $e->getMessage();
        }

        $this->cronJobLogRepository->createCronJobLog($cronJobLog);
    }
}
