<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsOutbox extends Model
{
    protected $table = 'sms_outboxes';

    protected $fillable = [
        'device_id','to','message','status','error','queued_at','sent_at'
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }
}
