<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface ExpireDocumentRepositoryInterface extends BaseRepositoryInterface
{
    public function getExpireDocuments($attributes);
    public function getExpireDocumentsCount($attributes);
    public function activeDocument($id);
}
