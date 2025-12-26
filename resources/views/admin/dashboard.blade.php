@extends('admin.layout')

@section('title','Dashboard')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Devices</div>
                    <div class="fs-3">{{ $deviceCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Heartbeats (24h)</div>
                    <div class="fs-3">{{ $last24h }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Heartbeats (1h)</div>
                    <div class="fs-3">{{ $last1h }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Devices - état actuel</div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Device</th>
                    <th>UID</th>
                    <th>Dernière vue</th>
                    <th>GSM</th>
                    <th>Opérateur</th>
                    <th>SIM</th>
                    <th>SMS</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($devices as $d)
                    <tr>
                        <td>{{ $d->name }}</td>
                        <td class="text-muted">{{ $d->device_uid }}</td>
                        <td>
                            @if($d->last_seen_at)
                                <span title="{{ $d->last_seen_at }}">{{ $d->last_seen_at->diffForHumans() }}</span>
                            @else
                                <span class="text-muted">Jamais</span>
                            @endif
                        </td>
                        <td>
                            @if(!is_null($d->last_signal_percent))
                                <div class="progress" style="height: 18px;">
                                    <div class="progress-bar" style="width: {{ $d->last_signal_percent }}%">
                                        {{ $d->last_signal_percent }}%
                                    </div>
                                </div>
                                <div class="small text-muted">{{ $d->last_dbm }} dBm</div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $d->last_operator ?? '-' }}</td>
                        <td>
                            {{ $d->last_sim_status ?? '-' }}
                            @if($d->last_roaming) <span class="badge text-bg-warning">Roaming</span> @endif
                        </td>
                        <td>S: {{ $d->last_sent_count }} / R: {{ $d->last_recv_count }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('admin.device',$d->id) }}">Détails</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
