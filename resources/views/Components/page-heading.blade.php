@props([
    'title',
    'subtitle' => null,
    'action' => null,
    'actionLabel' => 'Add New',
])

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-muted mb-0 small">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($action)
        <a href="{{ $action }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ $actionLabel }}
        </a>
    @endif
</div>