<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'role', 'status', 'password', 'module_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public $incrementing = true;

    protected $keyType = 'int';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'module_access' => 'array',
        ];
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function isAdmin(): bool
    {
        return strtolower((string) ($this->role ?? '')) === 'admin';
    }

    public function canAccessPharmacy(): bool
    {
        return $this->canAccessModule('pharmacy');
    }

    /**
     * @return array<string, array{label: string, group: string}>
     */
    public static function availableModules(): array
    {
        return config('modules', []);
    }

    /**
     * @return array<string, array<string, array{label: string, group: string}>>
     */
    public static function moduleGroups(): array
    {
        return collect(self::availableModules())
            ->groupBy('group', true)
            ->map(fn ($modules): array => $modules->all())
            ->all();
    }

    public function canAccessModule(string $module): bool
    {
        return $this->isAdmin()
            || in_array($module, $this->module_access ?? [], true);
    }

    public function canSeeInvoiceAmounts(): bool
    {
        return in_array(strtolower((string) ($this->role ?? '')), ['admin', 'accountant'], true);
    }
}
