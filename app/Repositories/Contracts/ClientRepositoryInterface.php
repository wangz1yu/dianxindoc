<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface ClientRepositoryInterface extends BaseRepositoryInterface
{
    public function deleteClient($id);
}
