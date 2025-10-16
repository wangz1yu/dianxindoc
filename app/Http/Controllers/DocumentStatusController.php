<?php

namespace App\Http\Controllers;

use App\Models\DocumentStatus;
use Illuminate\Http\Request;
use App\Repositories\Contracts\DocumentStatusRepositoryInterface;

class DocumentStatusController extends Controller
{
    private $documentStatusRepository;

    public function __construct(DocumentStatusRepositoryInterface $documentStatusRepository)
    {
        $this->documentStatusRepository = $documentStatusRepository;
    }

    public function index()
    {
        return response($this->documentStatusRepository->orderBy('createdDate')->all(), 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $existingStatus = DocumentStatus::where('name', $request->name)
            ->where('id', '!=', $id)
            ->first();

        if ($existingStatus) {
            return response()->json([
                'messages' => ['Status name already exists.']
            ], 409);
        }
        return  $this->documentStatusRepository->updateStatus($request->all(), $id);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $existingStatus = DocumentStatus::where('name', $request->name)->first();

        if ($existingStatus) {
            return response()->json([
                'messages' => ['Status name already exists.']
            ], 409);
        }

        return  response($this->documentStatusRepository->create($request->all()), 201);
    }

    public function get($id)
    {
        $fileRequest = $this->documentStatusRepository->find($id);
        return response($fileRequest, 201);
    }

    public function delete($id)
    {
        $isDeleted = $this->documentStatusRepository->deleteStatus($id);
        if ($isDeleted == true) {
            return response()->json([], 200);
        } else {
            return response()->json([
                'message' => 'Status can not be deleted. Document is assign to this status.',
            ], 404);
        }
    }
}
