<?php

namespace App\Repositories\Implementation;

use App\Models\DocumentMetaDatas;
use App\Models\DocumentAuditTrails;
use App\Models\DocumentComments;
use App\Models\DocumentOperationEnum;
use App\Models\DocumentRolePermissions;
use App\Models\Documents;
use App\Models\DocumentTokens;
use App\Models\DocumentUserPermissions;
use App\Models\DocumentVersions;
use App\Models\Reminders;
use App\Models\ReminderSchedulers;
use App\Models\RetentionActionEnum;
use App\Models\SendEmails;
use App\Models\UserNotifications;
use App\Models\UserNotificationTypeEnum;
use App\Models\UserRoles;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\DocumentIndexer;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Storage;

//use Your Model

/**
 * Class UserRepository.
 */
class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
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

    protected $indexer;

    public function __construct(DocumentIndexer $indexer)
    {
        parent::__construct();
        $this->indexer = $indexer;
    }

    public static function model()
    {
        return Documents::class;
    }

    public function getDocuments($attributes)
    {
        $query = Documents::select([
            'documents.id',
            'documents.name',
            'documents.url',
            'documents.createdDate',
            'documents.description',
            'documents.location',
            'documents.clientId',
            'documents.statusId',
            'documents.isIndexed',
            'documents.retentionPeriod',
            'documents.retentionAction',
            'categories.id as categoryId',
            'categories.name as categoryName',
            'clients.companyName as companyName',
            'documentStatus.name as statusName',
            'documentStatus.colorCode as colorCode',
            'documents.documentWorkflowId',
            'workflows.name as workflowName',
            'documentWorkflow.status as documentWorkflowStatus',
            DB::raw("CONCAT(signByUser.firstName,' ', signByUser.lastName) as signByUserName"),
            'documents.signDate',
            DB::raw('IF(documentWorkflow.status = "Completed" OR documentWorkflow.status = "Cancelled", true, false) as isWorkflowCompleted'),
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName"),
            DB::raw('(SELECT COUNT(*) FROM documentComments WHERE documentComments.documentId = documents.id) as commentCount'),
            DB::raw('(SELECT COUNT(*) FROM documentVersions WHERE documentVersions.documentId = documents.id) as versionCount')
        ])->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id')
            ->leftJoin('documentWorkflow', 'documents.documentWorkflowId', '=', 'documentWorkflow.id')
            ->leftJoin('workflows', 'documentWorkflow.workflowId', '=', 'workflows.id')
            ->leftjoin('users as signByUser', 'signByUser.id', '=', 'documents.signById');

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'name') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'createdDate') {
            $query = $query->orderBy('documents.createdDate', $direction);
        } else if ($orderBy == 'createdBy') {
            $query = $query->orderBy('users.firstName', $direction);
        } else if ($orderBy == 'location') {
            $query = $query->orderBy('documents.location', $direction);
        } else if ($orderBy == 'companyName') {
            $query = $query->orderBy('clients.companyName', $direction);
        } else if ($orderBy == 'statusName') {
            $query = $query->orderBy('documentStatus.name', $direction);
        }


        if ($attributes->categoryId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('categoryId', $attributes->categoryId)
                    ->orWhere('categories.parentId', $attributes->categoryId);
            });
        }

        if ($attributes->clientId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('clientId', $attributes->clientId);
            });
        }

        if ($attributes->statusId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('statusId', $attributes->statusId);
            });
        }

        if ($attributes->name) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('documents.name', 'like', '%' . $attributes->name . '%')
                    ->orWhere('documents.description',  'like', '%' . $attributes->name . '%');
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

        if ($attributes->createDateString) {

            $startDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC');
            $endDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query = $query->whereBetween('documents.createdDate', [$startDate, $endDate]);
        }

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function getDocumentsCount($attributes)
    {
        $query = Documents::query()
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id');

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

        if ($attributes->location) {
            $query = $query->where('documents.location', '=',  $attributes->location);
        }

        if ($attributes->clientId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('clientId', $attributes->clientId);
            });
        }
        if ($attributes->statusId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('statusId', $attributes->statusId);
            });
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

        if ($attributes->createDateString) {

            $startDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC');
            $endDate = Carbon::parse($attributes->createDateString)->setTimezone('UTC')->addDays(1)->addSeconds(-1);

            $query = $query->whereBetween('documents.createdDate', [$startDate, $endDate]);
        }

        $count = $query->count();
        return $count;
    }

    public function getDeepSearchDocuments($attributes)
    {
        $results = $this->indexer->search($attributes->searchQuery, 10);
        $documentIds = $results['ids'];

        if (!isset($results['ids']) || empty($results['ids'])) {
            return [];
        }

        $query = Documents::select([
            'documents.id',
            'documents.name',
            'documents.url',
            'documents.createdDate',
            'documents.description',
            'documents.location',
            'documents.isIndexed',
            'documents.retentionPeriod',
            'documents.retentionAction',
            'categories.id as categoryId',
            'categories.name as categoryName',
            'documents.clientId',
            'documents.statusId',
            'clients.companyName as companyName',
            'documentStatus.name as statusName',
            'documentStatus.colorCode as colorCode',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName")
        ])
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id');

        $query = $query->whereIn('documents.id', $documentIds);

        $results = $query->get();

        return $results;
    }

    public function saveDocument($request, $path, $fileSize)
    {
        try {
            $isIndexed = $fileSize < 3000000;
            DB::beginTransaction();
            $model = $this->model->newInstance($request);
            $model->url = $path;
            $model->categoryId = $request->categoryId;
            $model->clientId = $request->clientId;
            $model->statusId = $request->statusId;
            $model->name = $request->name;
            $model->location = $request->location;
            $model->description = $request->description;
            $metaDatas = $request->documentMetaDatas;
            $model->isIndexed = $isIndexed;
            $model->retentionPeriod = $request->retentionPeriod;
            $model->retentionAction = $request->retentionAction;
            $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            foreach (json_decode($metaDatas) as $metaTag) {
                DocumentMetaDatas::create(array(
                    'documentId' =>   $result->id,
                    'metatag' =>  $metaTag->metatag,
                ));
            }

            // create index of document.
            if ($isIndexed) {
                $this->indexer->createDocumentIndex($result->id, $path, $request->location);
            }

            $documentRolePermissions = json_decode($request->documentRolePermissions);
            $rolePermissionsArray = array();
            foreach ($documentRolePermissions as $docuemntrole) {
                $startDate = '';
                $endDate = '';
                if ($docuemntrole->isTimeBound) {
                    $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole->startDate)));
                    $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntrole->endDate)));
                    $startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                }

                DocumentRolePermissions::create([
                    'documentId' => $result->id,
                    'endDate' => $endDate  ?? '',
                    'isAllowDownload' => $docuemntrole->isAllowDownload,
                    'isTimeBound' => $docuemntrole->isTimeBound,
                    'roleId' => $docuemntrole->roleId,
                    'startDate' => $startDate ?? ''
                ]);

                DocumentAuditTrails::create([
                    'documentId' => $result->id,
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToRoleId' => $docuemntrole->roleId
                ]);

                $userIds = UserRoles::select('userId')
                    ->where('roleId', $docuemntrole->roleId)
                    ->get();

                foreach ($userIds as $userIdObject) {
                    array_push($rolePermissionsArray, [
                        'documentId' => $result->id,
                        'userId' => $userIdObject->userId
                    ]);
                }
            }

            $documentUserPermissions = json_decode($request->documentUserPermissions);
            foreach ($documentUserPermissions as $docuemntUser) {
                $startDate = '';
                $endDate = '';
                if ($docuemntUser->isTimeBound) {
                    $startdate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser->startDate)));
                    $enddate1 = date('Y-m-d', strtotime(str_replace('/', '-', $docuemntUser->endDate)));
                    $startDate = Carbon::createFromFormat('Y-m-d', $startdate1)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $enddate1)->endOfDay();
                }

                DocumentUserPermissions::create([
                    'documentId' => $result->id,
                    'endDate' => $endDate  ?? '',
                    'isAllowDownload' => $docuemntUser->isAllowDownload,
                    'isTimeBound' => $docuemntUser->isTimeBound,
                    'userId' => $docuemntUser->userId,
                    'startDate' => $startDate ?? ''
                ]);

                DocumentAuditTrails::create([
                    'documentId' => $result->id,
                    'createdDate' =>  Carbon::now(),
                    'operationName' => DocumentOperationEnum::Add_Permission->value,
                    'assignToUserId' => $docuemntUser->userId
                ]);


                array_push($rolePermissionsArray, [
                    'documentId' => $result->id,
                    'userId' => $docuemntUser->userId
                ]);
            }


            $rolePermissions = array_unique($rolePermissionsArray, SORT_REGULAR);
            foreach ($rolePermissions as $rolePermission) {
                UserNotifications::create([
                    'documentId' => $result->id,
                    'userId' => $rolePermission['userId'],
                    'notificationType' => UserNotificationTypeEnum::DOCUMENT_SHARE->value,
                    'createdDate' => $this->startDate ?? Carbon::now()
                ]);
            }

            $userId = Auth::parseToken()->getPayload()->get('userId');

            $array = array_filter($documentUserPermissions, function ($item) use ($userId) {
                return $item->userId == $userId;
            });

            if (count($array) == 0) {
                DocumentUserPermissions::create(array(
                    'documentId' =>   $result->id,
                    'userId' =>  $userId,
                    'isAllowDownload' => true
                ));
            }
            DB::commit();
            $result->id = (string)$result->id;
            return response()->json($result);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function updateDocument($request, $id)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->find($id);

            $model->name = $request->name;
            $model->description = $request->description;
            $model->categoryId = $request->categoryId;
            $model->clientId = $request->clientId;
            $model->statusId = $request->statusId;
            $model->retentionPeriod = $request->retentionPeriod;
            $model->retentionAction = $request->retentionAction;
            $metaDatas = $request->documentMetaDatas;
            $model->save();
            $this->resetModel();
            $result = $this->parseResult($model);

            $documentMetadatas = DocumentMetaDatas::where('documentId', '=', $id)->get('id');
            DocumentMetaDatas::destroy($documentMetadatas);

            foreach ($metaDatas as $metaTag) {
                DocumentMetaDatas::create(array(
                    'documentId' =>  $id,
                    'metatag' =>  $metaTag['metatag'],
                ));
            }

            DB::commit();
            return $result;;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error in saving data.',
            ], 409);
        }
    }

    public function assignedDocuments($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $query = Documents::select([
            'documents.id',
            'documents.name',
            'documents.url',
            'documents.createdDate',
            'documents.description',
            'documents.isIndexed',
            'categories.id as categoryId',
            'categories.name as categoryName',
            'documents.location',
            'documents.clientId',
            'documents.statusId',
            'documents.retentionPeriod',
            'documents.retentionAction',
            'clients.companyName as companyName',
            'documentStatus.name as statusName',
            'documentStatus.colorCode as colorCode',
            'documents.documentWorkflowId',
            'workflows.name as workflowName',
            'documentWorkflow.status as documentWorkflowStatus',
            DB::raw("CONCAT(signByUser.firstName,' ', signByUser.lastName) as signByUserName"),
            'documents.signDate',
            DB::raw('IF(documentWorkflow.status = "Completed" OR documentWorkflow.status = "Cancelled", true, false) as isWorkflowCompleted'),
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName"),
            DB::raw("(SELECT max(documentUserPermissions.endDate) FROM documentUserPermissions
                     WHERE documentUserPermissions.documentId = documents.id and documentUserPermissions.isTimeBound =1
                     GROUP BY documentUserPermissions.documentId) as maxUserPermissionEndDate"),
            DB::raw("(SELECT max(documentRolePermissions.endDate) FROM documentRolePermissions
                     WHERE documentRolePermissions.documentId = documents.id and documentRolePermissions.isTimeBound =1
                     GROUP BY documentRolePermissions.documentId) as maxRolePermissionEndDate"),
            DB::raw('(SELECT COUNT(*) FROM documentComments WHERE documentComments.documentId = documents.id) as commentCount'),
            DB::raw('(SELECT COUNT(*) FROM documentVersions WHERE documentVersions.documentId = documents.id) as versionCount')
        ])
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id')
            ->leftJoin('documentWorkflow', 'documents.documentWorkflowId', '=', 'documentWorkflow.id')
            ->leftJoin('workflows', 'documentWorkflow.workflowId', '=', 'workflows.id')
            ->leftjoin('users as signByUser', 'signByUser.id', '=', 'documents.signById')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
                        ->where(function ($query) {
                            $query->where('documentUserPermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentUserPermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentUserPermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentUserPermissions.endDate', '>=', $endDate);
                                });
                        });
                })->orWhereExists(function ($query) use ($userRoles) {
                    $query->select(DB::raw(1))
                        ->from('documentRolePermissions')
                        ->whereRaw('documentRolePermissions.documentId = documents.id')
                        ->whereIn('documentRolePermissions.roleId', $userRoles)
                        ->where(function ($query) {
                            $query->where('documentRolePermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentRolePermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentRolePermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentRolePermissions.endDate', '>=', $endDate);
                                });
                        });
                });
            });

        $orderByArray =  explode(' ', $attributes->orderBy);
        $orderBy = $orderByArray[0];
        $direction = $orderByArray[1] ?? 'asc';

        if ($orderBy == 'categoryName') {
            $query = $query->orderBy('categories.name', $direction);
        } else if ($orderBy == 'name') {
            $query = $query->orderBy('documents.name', $direction);
        } else if ($orderBy == 'createdDate') {
            $query = $query->orderBy('documents.createdDate', $direction);
        } else if ($orderBy == 'createdBy') {
            $query = $query->orderBy('users.firstName', $direction);
        } else if ($orderBy == 'location') {
            $query = $query->orderBy('documents.location', $direction);
        } else if ($orderBy == 'companyName') {
            $query = $query->orderBy('clients.companyName', $direction);
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

        if ($attributes->clientId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('clientId', $attributes->clientId);
            });
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

        $results = $query->skip($attributes->skip)->take($attributes->pageSize)->get();

        return $results;
    }

    public function assignedDocumentsCount($attributes)
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();
        $query = Documents::query()
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
                        ->where(function ($query) {
                            $query->where('documentUserPermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentUserPermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentUserPermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentUserPermissions.endDate', '>=', $endDate);
                                });
                        });
                })->orWhereExists(function ($query) use ($userRoles) {
                    $query->select(DB::raw(1))
                        ->from('documentRolePermissions')
                        ->whereRaw('documentRolePermissions.documentId = documents.id')
                        ->whereIn('documentRolePermissions.roleId', $userRoles)
                        ->where(function ($query) {
                            $query->where('documentRolePermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentRolePermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentRolePermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentRolePermissions.endDate', '>=', $endDate);
                                });
                        });
                });
            });

        if ($attributes->location) {
            $query = $query->where('documents.location', '=',  $attributes->location);
        }

        if ($attributes->clientId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('clientId', $attributes->clientId);
            });
        }

        if ($attributes->statusId) {
            $query = $query->where(function ($query) use ($attributes) {
                $query->where('statusId', $attributes->statusId);
            });
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

    public function getDocumentByCategory()
    {
        $userId = Auth::parseToken()->getPayload()->get('userId');
        $userRoles = UserRoles::select('roleId')
            ->where('userId', $userId)
            ->get();

        $query = Documents::select(['documents.categoryId', 'categories.name as categoryName',  DB::raw('count(*) as documentCount')])
            ->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->where(function ($query) use ($userId, $userRoles) {
                $query->whereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('documentUserPermissions')
                        ->whereRaw('documentUserPermissions.documentId = documents.id')
                        ->where('documentUserPermissions.userId', '=', $userId)
                        ->where(function ($query) {
                            $query->where('documentUserPermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentUserPermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentUserPermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentUserPermissions.endDate', '>=', $endDate);
                                });
                        });
                })->orWhereExists(function ($query) use ($userRoles) {
                    $query->select(DB::raw(1))
                        ->from('documentRolePermissions')
                        ->whereRaw('documentRolePermissions.documentId = documents.id')
                        ->whereIn('documentRolePermissions.roleId', $userRoles)
                        ->where(function ($query) {
                            $query->where('documentRolePermissions.isTimeBound', '=', '0')
                                ->orWhere(function ($query) {
                                    $date = date('Y-m-d');
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                                    $query->where('documentRolePermissions.isTimeBound', '=', '1')
                                        ->whereDate('documentRolePermissions.startDate', '<=', $startDate)
                                        ->whereDate('documentRolePermissions.endDate', '>=', $endDate);
                                });
                        });
                });
            });

        $results =  $query->groupBy('documents.categoryId', 'categories.name')->get();

        return $results;
    }

    public function getDocumentbyId($id)
    {
        $query = Documents::select([
            'documents.id',
            'documents.name',
            'documents.url',
            'documents.createdDate',
            'documents.modifiedDate',
            'documents.description',
            'documents.isIndexed',
            'documents.retentionPeriod',
            'documents.retentionAction',
            'categories.id as categoryId',
            'categories.name as categoryName',
            'documents.location',
            'documents.clientId',
            'documents.statusId',
            'clients.companyName as companyName',
            'documentStatus.name as statusName',
            'documentStatus.colorCode as colorCode',
            DB::raw("CONCAT(users.firstName,' ', users.lastName) as createdByName"),
            DB::raw("CONCAT(modifier.firstName,' ', modifier.lastName) as updatedByName")
        ])->join('categories', 'documents.categoryId', '=', 'categories.id')
            ->join('users', 'documents.createdBy', '=', 'users.id')
            ->leftJoin('users as modifier', 'documents.modifiedBy', '=', 'modifier.id')
            ->leftJoin('clients', 'documents.clientId', '=', 'clients.id')
            ->leftJoin('documentStatus', 'documents.statusId', '=', 'documentStatus.id')
            ->where('documents.id',  '=', $id);

        $document = $query->first();

        return $document;
    }

    public function archiveDocument($id)
    {
        try {
            $model = Documents::withoutGlobalScope('isExpired')->find($id);

            if ($model == null) {
                return response()->json([
                    'message' => 'Document not found.',
                ], 404);
            }

            $userId = Auth::parseToken()->getPayload()->get('userId');
            $model->isDeleted = true;
            $model->deletedBy = $userId;
            $model->isExpired = false;
            $model->expiredDate = null;
            $model->retentionPeriod = null;
            $model->retentionAction = null;
            $model->modifiedDate = Carbon::now();
            $model->modifiedBy = $userId;
            $model->deleted_at = Carbon::now();

            $model->save();
            // delete index of document.
            $isIndexed = filter_var($model->isIndexed, FILTER_VALIDATE_BOOLEAN);
            if ($isIndexed == true) {
                $this->indexer->deleteDocumentIndex($id);
            }

            return response()->json([], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in deleting document.',
            ], 409);
        }
    }

    public function addDOocumentToDeepSearch($id)
    {
        try {
            $model = $this->model->find($id);
            if ($model == null) {
                return response()->json([
                    'message' => 'Document not found.',
                ], 404);
            }
            $model->isIndexed = true;
            $model->save();

            // create index of document.
            $this->indexer->createDocumentIndex($id, $model->url, $model->location);

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in adding document to deep search.',
            ], 409);
        }
    }

    public function removeDocumentFromDeepSearch($id)
    {
        try {
            $model = $this->model->find($id);
            if ($model == null) {
                return response()->json([
                    'message' => 'Document not found.',
                ], 404);
            }
            $model->isIndexed = false;
            $model->save();

            // delete index of document.
            $this->indexer->deleteDocumentIndex($id);

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in removing document from deep search.',
            ], 409);
        }
    }

    public function permenentDeleteDocumentsBySystemUser($documents, $systemUser)
    {
        $documentIds = $documents->pluck('id')->toArray();

        try {

            DB::beginTransaction();
            $medatDatas = DocumentMetaDatas::whereIn('documentId', $documentIds)->get();
            $comments = DocumentComments::whereIn('documentId', $documentIds)->get();
            $userNotifications = UserNotifications::whereIn('documentId', $documentIds)->get();
            $reminders = Reminders::withoutGlobalScope('isDeleted')->whereIn('documentId', $documentIds)->get();
            $reminderSchedulers = ReminderSchedulers::whereIn('documentId', $documentIds)->get();
            $documentVersions = DocumentVersions::withoutGlobalScope('isDeleted')->whereIn('documentId', $documentIds)->get();
            $userPermissions = DocumentUserPermissions::whereIn('documentId', $documentIds)->get();
            $rolePermissions = DocumentRolePermissions::whereIn('documentId', $documentIds)->get();
            $sendEmail = SendEmails::withoutGlobalScope('isDeleted')->whereIn('documentId', $documentIds)->get();
            $tokens = DocumentTokens::whereIn('documentId', $documentIds)->get();

            foreach ($medatDatas as $medatData) {
                $medatData->forceDelete();
            }

            foreach ($comments as $comment) {
                $comment->forceDelete();
            }

            foreach ($userNotifications as $userNotification) {
                $userNotification->forceDelete();
            }

            foreach ($reminders as $reminder) {
                $reminder->forceDelete();
            }

            foreach ($reminderSchedulers as $reminderScheduler) {
                $reminderScheduler->forceDelete();
            }

            foreach ($documentVersions as $documentVersion) {
                $documentVersion->forceDelete();
            }

            foreach ($userPermissions as $userPermission) {
                $userPermission->forceDelete();
            }

            foreach ($rolePermissions as $rolePermission) {
                $rolePermission->forceDelete();
            }

            foreach ($sendEmail as $sendEmail) {
                $sendEmail->forceDelete();
            }

            foreach ($tokens as $token) {
                $token->forceDelete();
            }

            foreach ($documents as $document) {
                $document->isDeleted = true;
                $document->ispermanentDelete = true;
                $document->deletedBy = $systemUser->id;
                $document->deleted_at = Carbon::now();
                $document->save();

                DocumentAuditTrails::create([
                    'documentId' => $document->id,
                    'createdDate' => Carbon::now(),
                    'operationName' => 'Deleted',
                    'createdBy' => $systemUser->id,
                    'modifiedBy' => $systemUser->id,
                ]);
            }

            DB::commit();

            foreach ($documents as $document) {
                // remove document index
                $isIndexed = filter_var($document->isIndexed, FILTER_VALIDATE_BOOLEAN);
                if ($isIndexed == true) {
                    try {
                        $this->indexer->deleteDocumentIndex($document->id);
                    } catch (\Throwable $th) {
                    }
                }

                try {
                    Storage::disk($document->location)->delete($document->path);
                } catch (\Throwable $th) {
                }
            }

            foreach ($documentVersions as $documentVersion) {
                $versionUrl = $documentVersion->url;
                $versionLocation = $documentVersion->location;

                try {
                    Storage::disk($versionLocation)->delete($versionUrl);
                } catch (\Throwable $th) {
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            echo $th->getMessage();
            return response()->json([
                'message' => $th->getMessage(),
            ], 409);
        }
    }

    public function deleteOrArchiveDocumentsByRetentionDate()
    {
        $systemUser = Users::withoutGlobalScope('isSystemUser')
            ->where('isSystemUser', 1)
            ->first();

        if (!$systemUser) {
            return response()->json([
                'message' => 'System user not found. Please create a system user before proceeding.',
            ], 409);
        }

        $now = Carbon::now()->toDateTimeString();

        try {
            $documentsForDelete =   Documents::whereNotNull('retentionPeriod')
                ->whereNotNull('retentionAction')
                ->whereRaw("DATE_ADD(createdDate, INTERVAL retentionPeriod DAY) <= ?", [$now])
                ->where("retentionAction", RetentionActionEnum::PERMANENT_DELETE->value)
                ->get();

            $this->permenentDeleteDocumentsBySystemUser($documentsForDelete, $systemUser);

            $documentsForArchive =   Documents::whereNotNull('retentionPeriod')
                ->whereNotNull('retentionAction')
                ->whereRaw("DATE_ADD(createdDate, INTERVAL retentionPeriod DAY) <= ?", [$now])
                ->where("retentionAction", RetentionActionEnum::ARCHIVE->value)
                ->get();

            $this->archiveDocumentsBySystemUser($documentsForArchive, $systemUser);

            $documentsForExpire = Documents::whereNotNull('retentionPeriod')
                ->whereNotNull('retentionAction')
                ->whereRaw("DATE_ADD(createdDate, INTERVAL retentionPeriod DAY) <= ?", [$now])
                ->where("retentionAction", RetentionActionEnum::EXPIRE->value)
                ->get();

            $this->expireDocumentBySystemUser($documentsForExpire, $systemUser);

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in removing document from deep search.',
            ], 409);
        }
    }

    private function archiveDocumentsBySystemUser($documents, $systemUser)
    {
        foreach ($documents as $document) {
            $document->isDeleted = true;
            $document->deletedBy = $systemUser->id;
            $document->deleted_at = Carbon::now();
            $document->save();

            $isIndexed = filter_var($document->isIndexed, FILTER_VALIDATE_BOOLEAN);
            if ($isIndexed == true) {
                $this->indexer->deleteDocumentIndex($document->id);
            }

            DocumentAuditTrails::create([
                'documentId' => $document->id,
                'createdDate' => Carbon::now(),
                'operationName' => DocumentOperationEnum::Archived->value,
                'createdBy' => $systemUser->id,
                'modifiedBy' => $systemUser->id,
            ]);
        }
    }


    private function expireDocumentBySystemUser($documents, $systemUser)
    {
        foreach ($documents as $document) {
            $document->isExpired = true;
            $document->expiredDate = Carbon::now();
            $document->save();

            DocumentAuditTrails::create([
                'documentId' => $document->id,
                'createdDate' => Carbon::now(),
                'operationName' => DocumentOperationEnum::Expired->value,
                'createdBy' => $systemUser->id,
                'modifiedBy' => $systemUser->id,
            ]);
        }
    }
}
