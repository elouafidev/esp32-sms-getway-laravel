<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SmsInbox;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::orderBy('name')->get();

        $q = SmsInbox::query()->with('device')->orderByDesc('id');

        if ($request->filled('device_id')) {
            $q->where('device_id', (int)$request->device_id);
        }
        if ($request->filled('from')) {
            $q->where('from', 'like', '%'.$request->from.'%');
        }
        if ($request->filled('message')) {
            $q->where('message', 'like', '%'.$request->message.'%');
        }

        $items = $q->paginate(50)->withQueryString();

        return view('admin.inbox', compact('devices','items'));
    }
}
