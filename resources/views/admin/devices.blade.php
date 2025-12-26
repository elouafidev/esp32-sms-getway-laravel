@extends('admin.layout')
@section('title','Devices')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="h4 mb-0">Devices</div>
        <a class="btn btn-primary" href="{{ route('admin.devices.create') }}">Nouveau</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>UID</th>
                    <th>Active</th>
                    <th>Last seen</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($devices as $d)
                    <tr>
                        <td>{{ $d->name }}</td>
                        <td class="text-muted">{{ $d->device_uid }}</td>
                        <td>{!! $d->is_active ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-danger">No</span>' !!}</td>
                        <td>{{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : 'Jamais' }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.device',$d->id) }}">Détails</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $devices->links() }}
        </div>
    </div>
@endsection
