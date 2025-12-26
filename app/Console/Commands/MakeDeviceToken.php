<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Device;

class MakeDeviceToken extends Command
{
    protected $signature = 'device:token {device_uid} {user_id=1}';
    protected $description = 'Generate a Sanctum token for ESP32 device usage';

    public function handle(): int
    {
        $deviceUid = $this->argument('device_uid');
        $userId = (int)$this->argument('user_id');

        $device = Device::where('device_uid', $deviceUid)->first();
        if (!$device) {
            $this->error("Device not found: {$deviceUid}");
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found: {$userId}");
            return 1;
        }

        // token abilities optional; keep simple
        $token = $user->createToken("device:{$deviceUid}")->plainTextToken;

        $this->info("TOKEN (Bearer):");
        $this->line($token);
        return 0;
    }
}
