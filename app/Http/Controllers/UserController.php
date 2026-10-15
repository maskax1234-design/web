<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends HospitalResourceController
{
    protected string $model = User::class;

    protected string $title = 'User';

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:32'],
        'role' => ['required', 'string', 'max:50'],
        'status' => ['required', 'string', 'max:30'],
        'password' => ['required', 'string', 'min:8'],
    ];

    /**
     * The password is only mandatory when creating an account.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function updateRules(): array
    {
        return array_merge($this->rules, [
            'password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $moduleGroups = User::moduleGroups();
        $actor = auth()->user();

        if ($actor instanceof User && ! $actor->isAdmin()) {
            $allowedModules = array_flip($actor->module_access ?? []);
            $moduleGroups = collect($moduleGroups)
                ->map(fn (array $modules): array => array_intersect_key($modules, $allowedModules))
                ->filter()
                ->all();
        }

        return [
            'moduleGroups' => $moduleGroups,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->canAccessModule('users'), 403);
        $request->merge(['module_access' => $request->input('module_access', [])]);
        $data = $request->validate(array_merge($this->rules, [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            ...$this->moduleAccessRules($actor),
        ]));
        $this->guardRoleAssignment($actor, $data['role']);
        $user = User::create($data);

        return redirect()
            ->route($this->routeName().'.show', $user)
            ->with('success', $this->title.' created.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->canAccessModule('users'), 403);
        /** @var User $user */
        $user = $this->find($record);
        abort_unless($actor->isAdmin() || ! $user->isAdmin(), 403);
        $request->merge(['module_access' => $request->input('module_access', [])]);
        $data = $request->validate(array_merge($this->updateRules(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            ...$this->moduleAccessRules($actor),
        ]));
        $this->guardRoleAssignment($actor, $data['role']);

        if (! $actor->isAdmin()) {
            $modulesOutsideActorAccess = array_diff($user->module_access ?? [], $actor->module_access ?? []);
            $data['module_access'] = array_values(array_unique([
                ...$modulesOutsideActorAccess,
                ...$data['module_access'],
            ]));
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guardLastActiveAdministrator($lockedUser, $data['role'], $data['status']);
            $lockedUser->update($data);
        });

        return redirect()
            ->route($this->routeName().'.show', $user)
            ->with('success', $this->title.' updated.');
    }

    public function destroy(string $record): RedirectResponse
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->canAccessModule('users'), 403);
        /** @var User $user */
        $user = $this->find($record);
        abort_unless($actor->isAdmin() || ! $user->isAdmin(), 403);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guardLastActiveAdministrator($lockedUser, null, null, true);
            $lockedUser->delete();
        });

        return redirect()
            ->route($this->routeName().'.index')
            ->with('success', $this->title.' removed.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function moduleAccessRules(User $actor): array
    {
        $allowedModules = $actor->isAdmin()
            ? array_keys(User::availableModules())
            : ($actor->module_access ?? []);

        return [
            'module_access' => ['present', 'array'],
            'module_access.*' => ['string', 'distinct', Rule::in($allowedModules)],
        ];
    }

    private function guardRoleAssignment(User $actor, string $role): void
    {
        if (! $actor->isAdmin() && strtolower($role) === 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Only administrators can assign the Admin role.',
            ]);
        }
    }

    private function guardLastActiveAdministrator(
        User $user,
        ?string $newRole,
        ?string $newStatus,
        bool $deleting = false,
    ): void {
        if (! $user->isAdmin() || strtolower((string) $user->status) !== 'active') {
            return;
        }

        $isDemotion = $deleting
            || strtolower((string) $newRole) !== 'admin'
            || strtolower((string) $newStatus) !== 'active';

        if (! $isDemotion) {
            return;
        }

        $activeAdministratorCount = User::query()
            ->whereRaw('LOWER(role) = ?', ['admin'])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->lockForUpdate()
            ->count();

        if ($activeAdministratorCount <= 1) {
            throw ValidationException::withMessages([
                'role' => 'The last active administrator cannot be demoted, suspended, or removed.',
            ]);
        }
    }
}
