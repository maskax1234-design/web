<x-layout title="Add Appointment">
    <div class="container">
        <x-page-heading
            title="Add Appointment"
            subtitle="Schedule a patient visit"
            :action="route('appointments.index')"
            action-label="Back to list"
        />

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('appointments.store') }}" method="POST" class="card card-body shadow-sm">
            @csrf

            <div class="row">
                @include('appointments._form')
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Appointment
                </button>
                <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout>