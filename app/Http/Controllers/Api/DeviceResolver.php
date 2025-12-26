<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;

class DeviceResolver
{
    public static function byUid(string $deviceUid): Device
    {
        $device = Device::where('device_uid', $deviceUid)->first();
        abort_if(!$device || !$device->is_active, 404, 'Device not found or inactive');
        return $device;
    }
}
