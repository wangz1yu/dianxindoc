<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentStatusRepositoryInterface extends BaseRepositoryInterface
{
    public function updateStatus($request, $id);
    public function deleteStatus($id);
}
