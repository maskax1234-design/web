<x-layout :title="$record->name">
    <div class="container">
        <x-page-heading
            :title="$record->name"
            :subtitle="$record->role"
            :action="(auth()->user()->isAdmin() || ! $record->isAdmin()) ? route('users.edit', $record) : null"
            action-label="Edit"
        />

        <div class="card shadow-sm">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8">{{ $record->name }}</dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $record->email }}</dd>

                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">{{ $record->phone ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Role</dt>
                    <dd class="col-sm-8"><span class="badge text-bg-light border">{{ $record->role }}</span></dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>

                    <dt class="col-sm-4 text-muted">Module Access</dt>
                    <dd class="col-sm-8">
                        @if ($record->isAdmin())
                            All modules (administrator access)
                        @else
                            {{ collect($record->module_access ?? [])->map(fn ($module) => \App\Models\User::availableModules()[$module]['label'] ?? null)->filter()->join(', ') ?: 'No modules assigned' }}
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Email Verified</dt>
                    <dd class="col-sm-8">{{ $record->email_verified_at?->format('d M Y') ?? 'Not verified' }}</dd>

                    <dt class="col-sm-4 text-muted">Created</dt>
                    <dd class="col-sm-8">{{ $record->created_at?->format('d M Y, H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <a href="{{ route('users.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
    </div>
</x-layout>