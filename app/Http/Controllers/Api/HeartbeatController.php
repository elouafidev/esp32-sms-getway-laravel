<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceStatus;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function store(Request $request, string $deviceUid)
    {
        $device = DeviceResolver::byUid($deviceUid);

        $data = $request->validate([
            'uptime_s' => 'nullable|integer|min:0',
            'wifi_rssi' => 'nullable|integer',
            'sent_count' => 'nullable|integer|min:0',
            'recv_count' => 'nullable|integer|min:0',
            'gsm' => 'required|array',
            'gsm.rssi_raw' => 'nullable|integer|min:0',
            'gsm.ber' => 'nullable|integer|min:0',
            'gsm.dbm' => 'nullable|integer',
            'gsm.signal_percent' => 'nullable|integer|min:0|max:100',
            'gsm.operator' => 'nullable|string|max:255',
            'gsm.sim_status' => 'nullable|string|max:255',
            'gsm.creg_stat' => 'nullable|integer|min:0|max:10',
            'gsm.roaming' => 'nullable|boolean',
            'gsm.iccid' => 'nullable|string|max:40',
            'gsm.imsi' => 'nullable|string|max:40',
        ]);

        $gsm = $data['gsm'];

        // Insert history row
        DeviceStatus::create([
            'device_id' => $device->id,
            'gsm_signal_percent' => $gsm['signal_percent'] ?? null,
            'gsm_dbm' => $gsm['dbm'] ?? null,
            'gsm_rssi_raw' => $gsm['rssi_raw'] ?? null,
            'gsm_ber' => $gsm['ber'] ?? null,
            'gsm_operator' => $gsm['operator'] ?? null,
            'sim_status' => $gsm['sim_status'] ?? null,
            'creg_stat' => $gsm['creg_stat'] ?? null,
            'roaming' => (bool)($gsm['roaming'] ?? false),
            'iccid' => $gsm['iccid'] ?? null,
            'imsi' => $gsm['imsi'] ?? null,
            'wifi_rssi' => $data['wifi_rssi'] ?? null,
            'sent_count' => $data['sent_count'] ?? 0,
            'recv_count' => $data['recv_count'] ?? 0,
            'uptime_s' => $data['uptime_s'] ?? null,
        ]);

        // Update device "last_*" cache
        $device->update([
            'last_seen_at' => now(),
            'last_operator' => $gsm['operator'] ?? $device->last_operator,
            'last_signal_percent' => $gsm['signal_percent'] ?? $device->last_signal_percent,
            'last_dbm' => $gsm['dbm'] ?? $device->last_dbm,
            'last_sim_status' => $gsm['sim_status'] ?? $device->last_sim_status,
            'last_creg_stat' => $gsm['creg_stat'] ?? $device->last_creg_stat,
            'last_roaming' => (bool)($gsm['roaming'] ?? $device->last_roaming),
            'last_sent_count' => $data['sent_count'] ?? $device->last_sent_count,
            'last_recv_count' => $data['recv_count'] ?? $device->last_recv_count,
            'last_wifi_rssi' => $data['wifi_rssi'] ?? $device->last_wifi_rssi,
        ]);

        return response()->json(['ok' => true]);
    }
}
