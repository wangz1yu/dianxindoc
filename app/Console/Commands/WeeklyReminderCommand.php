<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Repositories\Contracts\NotificationScheduleInterface;
use App\Repositories\Contracts\CronJobLogRepositoryInterface;
use Carbon\Carbon;  

class WeeklyReminderCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notification:weekly';

    /**
     * @var string
     */
    protected $description = 'Weekly Notification Handler.';

    /**
     * @var NotificationScheduleInterface
     */
    private $notificationRepository;
    private $cronJobLogRepository;

    /**
     * @param NotificationScheduleInterface $link
     */
    public function __construct(NotificationScheduleInterface $notificationRepository,
      CronJobLogRepositoryInterface   $cronJobLogRepository)
    {
        parent::__construct();
        $this->notificationRepository = $notificationRepository;
        $this->cronJobLogRepository = $cronJobLogRepository;
    }

    public function handle()
    {
        $cronJobLog = [
            'jobName' => 'Weekly Reminder',
            'startedAt' => Carbon::now(),
        ];
        try {
            $this->notificationRepository->weeklyReminder();
            $this->info('Weekly Reminder...');
            $cronJobLog['status'] = 'success';
        } catch (\Exception $e) {
            $cronJobLog['status'] = 'failed';
            $cronJobLog['output'] = $e->getMessage();
        }

        $this->cronJobLogRepository->createCronJobLog($cronJobLog);

    }
}
