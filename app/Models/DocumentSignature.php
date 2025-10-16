<?php

namespace App\Models;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class DocumentSignature extends Model
{
    use HasFactory, Uuids;
    protected $primaryKey = "id";
    public $table = 'documentSignatures';
    public $timestamps = false;

    protected $fillable = [
        'documentId',
        'createdBy',
        'signatureUrl',
        'createdDate'
    ];

    protected $casts = [
        'createdDate' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
