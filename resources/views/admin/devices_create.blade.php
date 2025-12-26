@extends('admin.layout')
@section('title','Créer Device')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Créer un device</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.devices.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Device UID</label>
                    <input class="form-control" name="device_uid" placeholder="esp32-001" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom</label>
                    <input class="form-control" name="name" placeholder="ESP32 Salon">
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
@endsection
