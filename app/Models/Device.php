<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'device_uid','name','is_active',
        'last_seen_at','last_operator','last_signal_percent','last_dbm',
        'last_sim_status','last_creg_stat','last_roaming',
        'last_sent_count','last_recv_count','last_wifi_rssi',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_roaming' => 'boolean',
        'last_signal_percent' => 'integer',
        'last_dbm' => 'integer',
        'last_wifi_rssi' => 'integer',
        'last_sent_count' => 'integer',
        'last_recv_count' => 'integer',
    ];

    public function statuses(): HasMany {
        return $this->hasMany(DeviceStatus::class);
    }

    public function outbox(): HasMany {
        return $this->hasMany(SmsOutbox::class);
    }

    public function inbox(): HasMany {
        return $this->hasMany(SmsInbox::class);
    }
}
