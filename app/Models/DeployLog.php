<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeployLog extends Model
{
    protected $fillable = [
        'rdm_instance_id',
        'step',
        'message',
        'progress',
        'level',
    ];

    public function rdmInstance(): BelongsTo
    {
        return $this->belongsTo(RdmInstance::class);
    }
}
