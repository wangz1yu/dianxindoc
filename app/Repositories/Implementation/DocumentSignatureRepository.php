<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentSignature;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentSignatureRepositoryInterface;
use Illuminate\Support\Facades\DB;

//use Your Model

/**
 * Class ActionsRepository.
 */
class DocumentSignatureRepository extends BaseRepository implements DocumentSignatureRepositoryInterface
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
        return DocumentSignature::class;
    }


    public function getDocumentSignature($id)
    {
        try {
            $signatures = DocumentSignature::select([
                'documentSignatures.createdDate',
                'documentSignatures.signatureUrl',
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as signatureBy")
            ])
                ->join('users', 'documentSignatures.createdBy', '=', 'users.id')
                ->where('documentId', $id)
                ->orderBy('documentSignatures.createdDate', 'desc')
                ->get();

            if ($signatures->isEmpty()) {
                return response()->json([], 200);
            }

            $signatures = $signatures->map(function ($signature) {
                if (
                    !empty($signature->signatureUrl)
                    && file_exists(storage_path('app' . DIRECTORY_SEPARATOR . $signature->signatureUrl))
                ) {
                    $imageData = file_get_contents(storage_path('app' . DIRECTORY_SEPARATOR . $signature->signatureUrl));
                    $signature->base64 = base64_encode($imageData);
                    $signature->signatureUrl = '';
                } else {
                    $signature->base64 = null;
                }
                return $signature;
            });
            return response()->json($signatures, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in fetching data.' . $e->getMessage(),
            ], 409);
        }
    }
}
