<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface DocumentSignatureRepositoryInterface extends BaseRepositoryInterface
{
    public function getDocumentSignature($id);
}
