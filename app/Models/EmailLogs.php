<?php

namespace App\Models;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\Uuid;

class EmailLogs extends Model
{
    use HasFactory, Uuids;
    protected $primaryKey = "id";
    public $table = 'emailLogs';
    public $timestamps = false;

    protected $fillable = [
        'senderEmail',
        'recipientEmail',
        'subject',
        'body',
        'status',
        'errorMessage',
        'sentAt'
    ];

    protected $casts = [
        'sentAt' => 'datetime'
    ];

    public function emailLogAttachments()
    {
        return $this->hasMany(EmailLogAttachments::class, 'emailLogId', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
            $model->sentAt = Carbon::now();
        });
    }
}
