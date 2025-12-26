<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsOutbox;
use Illuminate\Http\Request;

class OutboxController extends Controller
{
    public function index(Request $request, string $deviceUid)
    {
        $device = DeviceResolver::byUid($deviceUid);

        $limit = (int)($request->query('limit', 5));
        $limit = max(1, min(20, $limit));

        $items = SmsOutbox::query()
            ->where('device_id', $device->id)
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id','to','message']);

        return response()->json(['data' => $items]);
    }

    public function result(Request $request, string $deviceUid, int $smsId)
    {
        $device = DeviceResolver::byUid($deviceUid);

        $data = $request->validate([
            'status' => 'required|in:sent,failed',
            'error'  => 'nullable|string|max:255',
        ]);

        $sms = SmsOutbox::where('device_id', $device->id)->findOrFail($smsId);

        $sms->status = $data['status'];
        $sms->error = $data['status'] === 'failed' ? ($data['error'] ?? 'unknown') : null;
        $sms->sent_at = $data['status'] === 'sent' ? now() : null;
        $sms->save();

        return response()->json(['ok' => true]);
    }
}
