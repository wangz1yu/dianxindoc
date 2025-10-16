<?php

namespace App\Repositories\Implementation;

use App\Models\Documents;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\ExpireDocumentRepositoryInterface;
use App\Services\DocumentIndexer;

//use Your Model

/**
 * Class UserRepository.
 */
class ExpireDocumentRepository extends BaseRepository implements ExpireDocumentRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;
    /**
     * BaseRepository constructor..
     *
     * @param Model $model
     */


    public static function model()
    {
        return Documents::class;
    }

    public function getExpireDocuments($attributes)
    {
        try {

            $query = Documents::withoutGlobalScope('isExpired')
                ->where('documents.isExpired', '=', 1)
                ->join('categories', 'documents.categoryId', '=', 'categories.id')
                ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id');

            $orderByArray =  explode(' ', $attributes->orderBy);
            $orderBy = $orderByArray[0];
            $direction = $orderByArray[1] ?? 'asc';

            if ($orderBy == 'categoryName') {
                $query = $query->orderBy('categories.name', $direction);
            } else if ($orderBy == 'name') {
                $query = $query->orderBy('documents.name', $direction);
            } else if ($orderBy == 'location') {
                $query = $query->orderBy('documents.location', $direction);
            } else if ($orderBy == 'statusName') {
                $query = $query->orderBy('documentStatus.name', $direction);
            }

            if ($attributes->categoryId) {
                $query = $query->where(function ($query) use ($attributes) {
                    $query->where('categoryId', $attributes->categoryId)
                        ->orWhere('categories.parentId', $attributes->categoryId);
                });
            }

            if ($attributes->name) {
                $query = $query->where(function ($query) use ($attributes) {
                    $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                        ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
                });
            }

            if ($attributes->statusId) {
                $query = $query->where(function ($query) use ($attributes) {
                    $query->where('statusId', $attributes->statusId);
                });
            }

            if ($attributes->location) {
                $query = $query->where('documents.location', '=',  $attributes->location);
            }

            if ($attributes->metaTags) {
                $metaTags = $attributes->metaTags;
                $query = $query->whereExists(function ($query) use ($metaTags) {
                    $query->select(DB::raw(1))
                        ->from('documentMetaDatas')
                        ->whereRaw('documentMetaDatas.documentId = documents.id')
                        ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
                });
            }

            $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get([
                'documents.id',
                'documents.name',
                'documents.url',
                'documents.description',
                'documents.location',
                'documents.expiredDate',
                'categories.id as categoryId',
                'categories.name as categoryName',
                'documentStatus.name as statusName',
            ]);

            return $results;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getExpireDocumentsCount($attributes)
    {
        $query = Documents::withoutGlobalScope('isExpired')
            ->where('documents.isExpired', '=', 1)
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'name') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'location') {
            $query = $query->orderBy('documents.location', $direction);
        } else if ($orderBy == 'statusName') {
            $query = $query->orderBy('documentStatus.name', $direction);
        }

        if ($attributes->categoryId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('categoryId', $attributes->categoryId)
                    ->orWhere('categories.parentId', $attributes->categoryId);
            });
        }

        if ($attributes->name) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                    ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
            });
        }
        if ($attributes->statusId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('statusId', $attributes->statusId);
            });
        }

        if ($attributes->location) {
            $query = $query->where('documents.location', '=',  $attributes->location);
        }

        if ($attributes->metaTags) {
            $metaTags = $attributes->metaTags;
            $query = $query->whereExists(function ($query) use ($metaTags) {
                $query->select(DB::raw(1))
                    ->from('documentMetaDatas')
                    ->whereRaw('documentMetaDatas.documentId = documents.id')
                    ->where('documentMetaDatas.metatag', 'like', '%' . $metaTags . '%');
            });
        }

        $count = $query->count();
        return $count;
    }

    public function activeDocument($id)
    {
        try {
            $document = Documents::withoutGlobalScope('isExpired')->findOrFail($id);
            $document->isExpired = 0;
            $document->expiredDate = null;
            $document->retentionPeriod = null;
            $document->retentionAction = null;
            $document->save();
            return response()->json([]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }
}
