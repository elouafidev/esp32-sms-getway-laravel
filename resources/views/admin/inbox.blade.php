@extends('admin.layout')
@section('title','Inbox')

@section('content')
    <div class="h4 mb-3">Inbox (SMS reçus)</div>

    <form class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" name="device_id">
                <option value="">Tous les devices</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" @selected(request('device_id')==$d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="from" placeholder="From contains..." value="{{ request('from') }}">
        </div>
        <div class="col-md-4">
            <input class="form-control" name="message" placeholder="Message contains..." value="{{ request('message') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-dark w-100">Filtrer</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Device</th>
                    <th>From</th>
                    <th>Message</th>
                    <th>Received</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $it)
                    <tr>
                        <td>{{ $it->id }}</td>
                        <td>{{ $it->device->name }}</td>
                        <td class="text-muted">{{ $it->from }}</td>
                        <td style="max-width:520px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $it->message }}
                        </td>
                        <td class="text-muted">{{ $it->received_at ?? $it->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $items->links() }}
        </div>
    </div>
@endsection
