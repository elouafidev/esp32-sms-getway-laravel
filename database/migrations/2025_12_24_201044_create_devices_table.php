<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_uid')->unique(); // ex: esp32-001
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_operator')->nullable();
            $table->unsignedTinyInteger('last_signal_percent')->nullable(); // 0..100
            $table->smallInteger('last_dbm')->nullable();
            $table->string('last_sim_status')->nullable(); // READY / SIM PIN / NOT INSERTED ...
            $table->unsignedTinyInteger('last_creg_stat')->nullable();
            $table->boolean('last_roaming')->default(false);

            $table->unsignedInteger('last_sent_count')->default(0);
            $table->unsignedInteger('last_recv_count')->default(0);
            $table->integer('last_wifi_rssi')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
