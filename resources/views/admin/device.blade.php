@extends('admin.layout')

@section('title', 'Device: '.$device->name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="h4 mb-1">{{ $device->name }}</div>
            <div class="text-muted">UID: {{ $device->device_uid }}</div>
        </div>
        <div class="text-end">
            <div class="text-muted">Last seen</div>
            <div class="fw-semibold">
                {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Jamais' }}
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Signal GSM (12h)</div>
                <div class="card-body">
                    <canvas id="chartSignal" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">RSSI Wi-Fi (12h)</div>
                <div class="card-body">
                    <canvas id="chartWifi" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">dBm GSM (12h)</div>
                <div class="card-body">
                    <canvas id="chartDbm" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Compteurs SMS (12h)</div>
                <div class="card-body">
                    <canvas id="chartSms" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Historique (50 / page)</div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Signal</th>
                    <th>dBm</th>
                    <th>Opérateur</th>
                    <th>SIM</th>
                    <th>CREG</th>
                    <th>WiFi RSSI</th>
                    <th>Sent</th>
                    <th>Recv</th>
                </tr>
                </thead>
                <tbody>
                @foreach($history as $h)
                    <tr>
                        <td class="text-muted">{{ $h->created_at }}</td>
                        <td>{{ is_null($h->gsm_signal_percent) ? 'N/A' : $h->gsm_signal_percent.'%' }}</td>
                        <td>{{ $h->gsm_dbm ?? 'N/A' }}</td>
                        <td>{{ $h->gsm_operator ?? '-' }}</td>
                        <td>
                            {{ $h->sim_status ?? '-' }}
                            @if($h->roaming) <span class="badge text-bg-warning">Roaming</span> @endif
                        </td>
                        <td>{{ $h->creg_stat ?? 'N/A' }}</td>
                        <td>{{ $h->wifi_rssi ?? 'N/A' }}</td>
                        <td>{{ $h->sent_count }}</td>
                        <td>{{ $h->recv_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $history->links() }}
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const labels = @json($labels);
        const signal = @json($signal);
        const dbm    = @json($dbm);
        const wifi   = @json($wifi);
        const sent   = @json($sent);
        const recv   = @json($recv);

        function makeLineChart(canvasId, label, data) {
            const ctx = document.getElementById(canvasId);
            return new Chart(ctx, {
                type: 'line',
                data: { labels, datasets: [{ label, data, tension: 0.25 }] },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: false } },
                    plugins: { legend: { display: true } }
                }
            });
        }

        makeLineChart('chartSignal', 'GSM Signal %', signal);
        makeLineChart('chartWifi', 'WiFi RSSI', wifi);
        makeLineChart('chartDbm', 'GSM dBm', dbm);

        // SMS counters on same chart
        new Chart(document.getElementById('chartSms'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Sent', data: sent, tension: 0.25 },
                    { label: 'Recv', data: recv, tension: 0.25 },
                ]
            },
            options: { responsive: true, plugins: { legend: { display: true } } }
        });
    </script>
@endsection
