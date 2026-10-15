<x-layout title="Add Doctor">
    <div class="container">
        <x-page-heading
            title="Add Doctor"
            subtitle="Register a new practitioner"
            :action="route('doctors.index')"
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

        <form action="{{ route('doctors.store') }}" method="POST" class="card card-body shadow-sm">
            @csrf

            <div class="row">
                @include('doctors._form')
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Doctor
                </button>
                <a href="{{ route('doctors.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout>