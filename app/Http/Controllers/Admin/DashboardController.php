<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $devices = Device::query()
            ->orderByDesc('last_seen_at')
            ->get();

        $deviceCount = $devices->count();

        $last24h = DeviceStatus::where('created_at', '>=', now()->subHours(24))->count();
        $last1h = DeviceStatus::where('created_at', '>=', now()->subHour())->count();

        return view('admin.dashboard', compact('devices','deviceCount','last24h','last1h'));
    }

    public function device(int $id)
    {
        $device = Device::findOrFail($id);

        // History table
        $history = $device->statuses()
            ->orderByDesc('id')
            ->paginate(50);

        // Chart data: last 12 hours (or last 200 points)
        $points = $device->statuses()
            ->where('created_at', '>=', now()->subHours(12))
            ->orderBy('id')
            ->limit(500)
            ->get(['created_at','gsm_signal_percent','gsm_dbm','wifi_rssi','sent_count','recv_count','gsm_operator','sim_status','creg_stat','roaming']);

        $labels = $points->map(fn($p) => $p->created_at->format('H:i'))->values();
        $signal = $points->map(fn($p) => $p->gsm_signal_percent)->values();
        $dbm    = $points->map(fn($p) => $p->gsm_dbm)->values();
        $wifi   = $points->map(fn($p) => $p->wifi_rssi)->values();
        $sent   = $points->map(fn($p) => $p->sent_count)->values();
        $recv   = $points->map(fn($p) => $p->recv_count)->values();

        return view('admin.device', compact('device','history','labels','signal','dbm','wifi','sent','recv'));
    }
}
