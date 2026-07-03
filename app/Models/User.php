<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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



    public function company(){
        return $this->belongsTo(Company::class);
    }


    public function service(){
        return $this->belongsTo(Service::class);
    }
}
