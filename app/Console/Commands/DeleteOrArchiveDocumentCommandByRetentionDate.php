<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Console\Command;
use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Carbon\Carbon;


class DeleteOrArchiveDocumentCommandByRetentionDate extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:deleteOrArchiveDocument';

    /**
     * @var string
     */
    protected $description = 'Delete Or Archive Document Handler.';

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepositoryInterface;
    private $cronJobLogRepository;


    /**
     * @param DocumentRepositoryInterface $link
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepositoryInterface,
        CronJobLogRepositoryInterface $cronJobLogRepository
    ) {
        parent::__construct();
        $this->documentRepositoryInterface = $documentRepositoryInterface;
        $this->cronJobLogRepository = $cronJobLogRepository;
    }

    public function handle()
    {
        $cronJobLog = [
            'jobName' => 'Delete or Archive or Expire Document By Retention Date',
            'startedAt' => Carbon::now(),
        ];

        try {
            $this->documentRepositoryInterface->deleteOrArchiveDocumentsByRetentionDate();
            $this->info('Delete or Archive or Expire Document...');
            $cronJobLog['status'] = 'success';
        } catch (\Exception $e) {
            $cronJobLog['status'] = 'failed';
            $cronJobLog['output'] = $e->getMessage();
        }

        $this->cronJobLogRepository->createCronJobLog($cronJobLog);
    }
}
