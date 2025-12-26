<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SmsOutbox;
use Illuminate\Http\Request;

class OutboxController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::orderBy('name')->get();

        $q = SmsOutbox::query()->with('device')->orderByDesc('id');

        if ($request->filled('device_id')) {
            $q->where('device_id', (int)$request->device_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $items = $q->paginate(50)->withQueryString();

        return view('admin.outbox', compact('devices','items'));
    }

    public function create()
    {
        $devices = Device::orderBy('name')->get();
        return view('admin.outbox_create', compact('devices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'to' => 'required|string|max:32',
            'message' => 'required|string|max:1000',
        ]);

        SmsOutbox::create([
            'device_id' => (int)$data['device_id'],
            'to' => $data['to'],
            'message' => $data['message'],
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        return redirect()->route('admin.outbox.index')->with('success', 'SMS ajouté à la file (queued).');
    }
}
