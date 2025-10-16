<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\ArchiveDocumentRepositoryInterface;
use Illuminate\Console\Command;
use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Carbon\Carbon;


class DeleteArchiveDocumentByRetentionDateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:deleteArchiveDocuments';

    /**
     * @var string
     */
    protected $description = 'Delete Archive Document Handler.';

    /**
     * @var ArchiveDocumentRepositoryInterface
     */
    private $archiveDocumentRepository;
    private $cronJobLogRepository;


    /**
     * @param ArchiveDocumentRepositoryInterface $link
     */
    public function __construct(
        ArchiveDocumentRepositoryInterface $archiveDocumentRepository,
        CronJobLogRepositoryInterface $cronJobLogRepository
    ) {
        parent::__construct();
        $this->archiveDocumentRepository = $archiveDocumentRepository;
        $this->cronJobLogRepository = $cronJobLogRepository;
    }

    public function handle()
    {
        $cronJobLog = [
            'jobName' => 'Delete Archive Document By Retention Date',
            'startedAt' => Carbon::now(),
        ];

        try {
            $this->archiveDocumentRepository->deleteArchiveDocumentByRetentionDateHandler();
            $this->info('Delete Archive Document...');
            $cronJobLog['status'] = 'success';
        } catch (\Exception $e) {
            $cronJobLog['status'] = 'failed';
            $cronJobLog['output'] = $e->getMessage();
        }

        $this->cronJobLogRepository->createCronJobLog($cronJobLog);
    }
}
