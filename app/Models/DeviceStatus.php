<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStatus extends Model
{
    protected $fillable = [
        'device_id',
        'gsm_signal_percent','gsm_dbm','gsm_rssi_raw','gsm_ber',
        'gsm_operator','sim_status','creg_stat','roaming',
        'iccid','imsi',
        'wifi_rssi','sent_count','recv_count','uptime_s',
    ];

    protected $casts = [
        'roaming' => 'boolean',
        'gsm_signal_percent' => 'integer',
        'gsm_dbm' => 'integer',
        'gsm_rssi_raw' => 'integer',
        'gsm_ber' => 'integer',
        'wifi_rssi' => 'integer',
        'sent_count' => 'integer',
        'recv_count' => 'integer',
        'uptime_s' => 'integer',
    ];

    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }
}
