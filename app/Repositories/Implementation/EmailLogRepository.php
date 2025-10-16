<?php

namespace App\Repositories\Implementation;

use App\Models\EmailLogAttachments;
use App\Models\EmailLogs;
use App\Repositories\Contracts\EmailLogRepositoryInterface;

class EmailLogRepository  extends BaseRepository  implements EmailLogRepositoryInterface
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public static function model()
    {
        return EmailLogs::class;
    }

    public function createLog($attribute, array $attachments)
    {
        try {
            $model = $this->model->newInstance($attribute);
            $model->save();
            $result = $this->parseResult($model);
            foreach ($attachments as $attachment) {
                EmailLogAttachments::create([
                    'emailLogId' => $result->id,
                    'path' => $attachment['path'],
                    'name' => $attachment['name']
                ]);
            }
        } catch (\Throwable $th) {
            // ignore
        }
    }


    public function getEmailLogs($attributes)
    {
        $query = EmailLogs::select(['emailLogs.*'])->with('emailLogAttachments');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        $query = $query->orderBy($orderBy, $direction);

        if ($attributes->senderEmail) {
            $query = $query->where('emailLogs.senderEmail',  'like', '%' . $attributes->senderEmail . '%');
        }

        if ($attributes->recipientEmail) {
            $query = $query->where('emailLogs.recipientEmail',  'like', '%' . $attributes->recipientEmail . '%');
        }

        if ($attributes->subject) {
            $query = $query->where('emailLogs.subject',  'like', '%' . $attributes->subject . '%');
        }

        return $query->skip($attributes->skip)->take($attributes->pageSize)->get();
    }

    public function getEmailLogCount($attributes)
    {
        $query = EmailLogs::query();

        if ($attributes->senderEmail) {
            $query = $query->where('emailLogs.senderEmail',  'like', '%' . $attributes->senderEmail . '%');
        }

        if ($attributes->recipientEmail) {
            $query = $query->where('emailLogs.recipientEmail',  'like', '%' . $attributes->recipientEmail . '%');
        }

        if ($attributes->subject) {
            $query = $query->where('emailLogs.subject',  'like', '%' . $attributes->subject . '%');
        }
        $count = $query->count();
        return $count;
    }

    public function deleteEmailLog($id)
    {
        try {
            $model = $this->model->with('emailLogAttachments')
                ->findOrFail($id);

            if ($model) {
                if (isset($model->emailLogAttachments)) {
                    foreach ($model->emailLogAttachments as $attachment) {
                        try {
                            $path = storage_path('app') . $attachment->path;
                            if (file_exists($path)) {
                                unlink($path);
                            }
                        } catch (\Throwable $th) {
                            //ignore
                        }
                        $attachment->delete();
                    }
                }
                $model->delete();
            }
            return response()->json([], 200);
        } catch (\Throwable $th) {
            return response()->json(['Message' => 'Error in deleting data.' . $th->getMessage()], 409);
        }
    }
}
