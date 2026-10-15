@props(['status'])

@php
    $tone = match (strtolower((string) $status)) {
        'active', 'available', 'completed', 'paid', 'confirmed', 'dispensed' => 'success',
        'admitted', 'scheduled', 'pending', 'in progress', 'partial', 'reserved' => 'info',
        'occupied', 'maintenance', 'no show', 'transferred' => 'warning',
        'cancelled', 'inactive', 'expired' => 'danger',
        default => 'secondary',
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge text-bg-'.$tone]) }}>{{ $status ?? '—' }}</span>