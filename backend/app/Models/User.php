<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\CausesActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use CausesActivity, HasFactory, LogsActivity, Notifiable;

    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    protected $attributes = ['is_admin' => false];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->exists && static::query()->whereKey($this->id)->where('is_admin', true)->exists();
    }

    protected static function booted(): void
    {
        $keepAdmin = function (self $user): void {
            if (static::query()->whereKey($user->id)->where('is_admin', true)->exists() && static::query()->where('is_admin', true)->count() <= 1) {
                throw ValidationException::withMessages(['is_admin' => 'Admin terakhir tidak dapat dihapus atau dicabut aksesnya.']);
            }
        };
        static::deleting($keepAdmin);
        static::updating(function (self $user) use ($keepAdmin): void {
            if ($user->isDirty('is_admin') && ! $user->is_admin) {
                $keepAdmin($user);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'is_admin'])->logOnlyDirty();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
