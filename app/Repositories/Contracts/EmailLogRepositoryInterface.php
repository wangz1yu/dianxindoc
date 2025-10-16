<?php

namespace App\Repositories\Contracts;

interface EmailLogRepositoryInterface
{
    public function createLog($attribute, array $attachments);
    public function getEmailLogs($attributes);
    public function getEmailLogCount($attributes);
    public function deleteEmailLog($id);
}
