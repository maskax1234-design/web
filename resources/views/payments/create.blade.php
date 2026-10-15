<x-layout title="Add Payment">
    <div class="container">
        <x-page-heading
            title="Add Payment"
            subtitle="Record a payment against an invoice"
            :action="route('payments.index')"
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

        <form action="{{ route('payments.store') }}" method="POST" class="card card-body shadow-sm">
            @csrf

            <div class="row">
                @include('payments._form')
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Payment
                </button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout>