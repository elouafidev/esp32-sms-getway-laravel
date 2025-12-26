@extends('admin.layout')
@section('title','Créer SMS')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Ajouter un SMS à envoyer</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.outbox.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Device</label>
                    <select class="form-select" name="device_id" required>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->device_uid }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Numéro</label>
                    <input class="form-control" name="to" placeholder="+336..." required>
                </div>
                <div class="col-12">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="message" rows="4" required></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Mettre en file (queued)</button>
                </div>
            </form>
        </div>
    </div>
@endsection
