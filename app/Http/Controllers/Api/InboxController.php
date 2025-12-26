<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsInbox;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function store(Request $request, string $deviceUid)
    {
        $device = DeviceResolver::byUid($deviceUid);

        $data = $request->validate([
            'from' => 'required|string|max:32',
            'message' => 'required|string|max:2000',
            'received_at' => 'nullable|integer', // ESP sends uptime timestamp; optional
        ]);

        SmsInbox::create([
            'device_id' => $device->id,
            'from' => $data['from'],
            'message' => $data['message'],
            'received_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
