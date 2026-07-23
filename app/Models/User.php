<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['full_name', 'email', 'password', 'is_active', 'service_id', 'code', 'company_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

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
        ];
    }

    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    public function withMergedPermissions()
    {
        $this->loadMissing('roles:id,name', 'permissions:id,name', 'roles.permissions:id,name');

        $mergedPermissions = $this->permissions
            ->merge($this->roles->flatMap->permissions)
            ->unique('id')
            ->values();

        $response = $this->toArray();

        $response['roles'] = collect($response['roles'])
            ->map(function ($role) {
                unset($role['permissions']);
                return $role;
            })
            ->values();

        $response['permissions'] = $mergedPermissions;

        return $response;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function responsibleForServices()
    {
        return $this->hasMany(ImprovementSheetResponsible::class, 'responsable_id');
    }

    /**
     * Corrective actions this user is responsible for carrying out.
     */
    public function correctiveActions()
    {
        return $this->hasMany(CorrectiveAction::class, 'responsable_id');
    }

    /**
     * Corrective actions created by this user.
     */
    public function createdCorrectiveActions()
    {
        return $this->hasMany(CorrectiveAction::class, 'user_id');
    }

    /**
     * Override to use our custom Notification model instead of
     * Laravel's default Illuminate\Notifications\DatabaseNotification.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function readNotifications(): MorphMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }
}