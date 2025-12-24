<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsInbox extends Model
{
    protected $table = 'sms_inboxes';

    protected $fillable = [
        'device_id','from','message','received_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }
}
