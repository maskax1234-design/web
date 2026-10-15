@props([
    'route',
    'record',
    'canManage' => true,
])

<div class="btn-group btn-group-sm">
    <a href="{{ route($route.'.show', $record) }}" class="btn btn-outline-info" title="Show">
        <i class="bi bi-eye"></i><span class="visually-hidden">Show</span>
    </a>
    @if ($canManage)
        <a href="{{ route($route.'.edit', $record) }}" class="btn btn-outline-warning" title="Edit">
            <i class="bi bi-pencil"></i><span class="visually-hidden">Edit</span>
        </a>
        <a
            href="{{ route($route.'.destroy', $record) }}"
            class="btn btn-outline-danger"
            title="Delete"
            onclick="return confirm('Are you sure you want to delete this record?')"
        >
            <i class="bi bi-trash"></i><span class="visually-hidden">Delete</span>
        </a>
    @endif
</div>