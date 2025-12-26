@extends('admin.layout')
@section('title','Outbox')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="h4 mb-0">Outbox (SMS à envoyer)</div>
        <a class="btn btn-primary" href="{{ route('admin.outbox.create') }}">Nouveau SMS</a>
    </div>

    <form class="row g-2 mb-3">
        <div class="col-md-4">
            <select class="form-select" name="device_id">
                <option value="">Tous les devices</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" @selected(request('device_id')==$d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Tous statuts</option>
                @foreach(['queued','sent','failed'] as $s)
                    <option value="{{ $s }}" @selected(request('status')==$s)>{{ $s }}</option>
                @endforeach
            </select>
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
                    <th>To</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Queued</th>
                    <th>Sent</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $it)
                    <tr>
                        <td>{{ $it->id }}</td>
                        <td>{{ $it->device->name }}</td>
                        <td class="text-muted">{{ $it->to }}</td>
                        <td style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $it->message }}
                        </td>
                        <td>
                            @if($it->status==='queued') <span class="badge text-bg-secondary">queued</span> @endif
                            @if($it->status==='sent') <span class="badge text-bg-success">sent</span> @endif
                            @if($it->status==='failed') <span class="badge text-bg-danger">failed</span> @endif
                        </td>
                        <td class="text-danger">{{ $it->error }}</td>
                        <td class="text-muted">{{ $it->queued_at }}</td>
                        <td class="text-muted">{{ $it->sent_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $items->links() }}
        </div>
    </div>
@endsection
