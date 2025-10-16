<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Http\Request;
use App\Repositories\Contracts\ExpireDocumentRepositoryInterface;

class ExpireDocumentController extends Controller
{
    private $archivedocumentRepository;
    private $documentRepository;
    protected $queryString;

    public function __construct(ExpireDocumentRepositoryInterface $archivedocumentRepository, DocumentRepositoryInterface $documentRepository)
    {
        $this->archivedocumentRepository = $archivedocumentRepository;
        $this->documentRepository = $documentRepository;
    }

    public function getExpireDocuments(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->archivedocumentRepository->getExpireDocumentsCount($queryString);
        return response()->json($this->archivedocumentRepository->getExpireDocuments($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function activeDocument($id)
    {
        return $this->archivedocumentRepository->activeDocument($id);
    }

    public function archivDocument($id)
    {
        return $this->documentRepository->archiveDocument($id);
    }
}
