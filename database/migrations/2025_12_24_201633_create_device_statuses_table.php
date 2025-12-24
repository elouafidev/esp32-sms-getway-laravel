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
        Schema::create('device_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('gsm_signal_percent')->nullable(); // 0..100
            $table->smallInteger('gsm_dbm')->nullable();
            $table->unsignedTinyInteger('gsm_rssi_raw')->nullable(); // 0..31/99
            $table->unsignedTinyInteger('gsm_ber')->nullable();

            $table->string('gsm_operator')->nullable();
            $table->string('sim_status')->nullable();
            $table->unsignedTinyInteger('creg_stat')->nullable();
            $table->boolean('roaming')->default(false);

            $table->string('iccid')->nullable();
            $table->string('imsi')->nullable();

            $table->integer('wifi_rssi')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('recv_count')->default(0);
            $table->unsignedInteger('uptime_s')->nullable();

            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_statuses');
    }
};
