<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Courrier;

/**
 * @method \Illuminate\Support\Collection getAllPermissions()
 * @method bool hasRole(string|array|\Spatie\Permission\Contracts\Role $roles, string|null $guard = null)
 * @method bool hasPermissionTo(string|\Spatie\Permission\Contracts\Permission $permission, string|null $guard = null)
 */
class User extends Authenticatable implements HasAvatar
{
    use HasFactory;
    use Notifiable;
    use HasRoles {
        hasRole as protected spatieHasRole;
    }
    use \Illuminate\Foundation\Auth\Access\Authorizable;

    public function gtt()
    {
        return $this->belongsTo(\App\Models\Gtt::class);
    }

    /**
     * Check if user has a given role name (helper for Filament/Spatie compatibility).
     */
    public function hasRole($roleName, ?string $guard = null): bool
    {
        return $this->spatieHasRole($roleName, $guard);
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            if ($user->roles()->exists()) {
                return;
            }

            $defaultRole = \Spatie\Permission\Models\Role::query()
                ->where('name', 'Lecteur Courrier')
                ->where('guard_name', 'web')
                ->first();

            if ($defaultRole) {
                $user->assignRole($defaultRole);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'departement_id',
        'gtt_id',
        'poste',
        'is_active',
        'avatar_path',
        'hire_date',
        'phone',
        'personal_email',
        'address',
        'bio',
        'cv_path',
        'preferences',
        'inactivity_timeout_minutes',
        'last_password_changed_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_active' => 'boolean',
            'hire_date' => 'date',
            'preferences' => 'array',
            'inactivity_timeout_minutes' => 'integer',
            'last_password_changed_at' => 'datetime',
        ];
    }

    // Cette méthode contrôle l'accès au panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->avatar_path, '/'));
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function imputationsRecues()
    {
        return $this->hasMany(Imputation::class, 'destinataire_id');
    }

    public function imputationsEnvoyees()
    {
        return $this->hasMany(Imputation::class, 'expediteur_id');
    }

    public function approvalsToApprove()
    {
        return $this->hasMany(CourrierApproval::class, 'approver_id');
    }

    public function reportApprovalsToApprove()
    {
        return $this->hasMany(ReportApproval::class, 'approver_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function assignedByMeTasks()
    {
        return $this->hasMany(Task::class, 'assigner_id');
    }

    public function mentions()
    {
        return $this->hasMany(Mention::class, 'mentioned_user_id');
    }

    public function createdAppointments(): HasMany
{
    return $this->hasMany(Appointment::class, 'created_by');
}

public function assignedAppointments(): HasMany
{
    return $this->hasMany(Appointment::class, 'assigned_to');
}

public function createdVisits(): HasMany
{
    return $this->hasMany(Visit::class, 'created_by');
}

public function facilitatedMeetings(): HasMany
{
    return $this->hasMany(Meeting::class, 'facilitator_id');
}

public function meetings(): BelongsToMany
{
    return $this->belongsToMany(Meeting::class, 'meeting_participants')
        ->withPivot(['role', 'attendance_status'])
        ->withTimestamps();
}

public function meetingTasks(): HasMany
{
    return $this->hasMany(MeetingTask::class, 'assigned_to');
}

public function syncDevices(): HasMany
{
    return $this->hasMany(SyncDevice::class);
}

public function offlineTasks(): HasMany
{
    return $this->hasMany(OfflineTask::class);
}

// C4 — Courriers initiés par l'utilisateur (user_id)
public function courriers(): HasMany
{
    return $this->hasMany(Courrier::class, 'user_id');
}
}
