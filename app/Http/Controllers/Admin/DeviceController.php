<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::orderByDesc('last_seen_at')->paginate(50);
        return view('admin.devices', compact('devices'));
    }

    public function create()
    {
        return view('admin.devices_create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_uid' => 'required|string|max:64|unique:devices,device_uid',
            'name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $device = Device::create([
            'device_uid' => $data['device_uid'],
            'name' => $data['name'] ?? $data['device_uid'],
            'is_active' => (bool)($data['is_active'] ?? true),
        ]);

        // Option: generate token for device (Sanctum)
        // We'll do token generation in a separate endpoint/command for simplicity.
        return redirect()->route('admin.devices.index')->with('success', 'Device créé. Génère ensuite le token Sanctum.');
    }
}
