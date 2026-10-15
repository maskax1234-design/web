<x-layout title="Add Department">
    <div class="container">
        <x-page-heading
            title="Add Department"
            subtitle="Create a new clinical department"
            :action="route('departments.index')"
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

        <form action="{{ route('departments.store') }}" method="POST" class="card card-body shadow-sm">
            @csrf

            <div class="row">
                @include('departments._form')
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Department
                </button>
                <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout>