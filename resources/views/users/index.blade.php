<x-layout title="Users">
    <div class="container">
        <x-page-heading
            title="Users"
            :subtitle="$records->total().' staff accounts'"
            :action="auth()->user()->canAccessModule('users') ? route('users.create') : null"
            action-label="Add User"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?: '—' }}</td>
                                <td><span class="badge text-bg-light border">{{ $user->role }}</span></td>
                                <td><x-badge :status="$user->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$user" :can-manage="auth()->user()->isAdmin() || ! $user->isAdmin()" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $records->links() }}
        </div>
    </div>
</x-layout>