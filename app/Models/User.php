<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'school_id',
        'is_independent',
        'name',
        'email',
        'password',
        'role',
        'candidate_number',
        'parent_email',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_independent'    => 'boolean',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isTeacher(): bool   { return $this->role === 'teacher'; }
    public function isStudent(): bool   { return $this->role === 'student'; }
    public function isAdmin(): bool     { return in_array($this->role, ['super_admin', 'school_admin']); }
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }

    // ── Relationships ─────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function ownedClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function enrolledClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student', 'student_id', 'classroom_id')
                    ->withPivot('joined_at');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function sentClassroomFriendInvites(): HasMany
    {
        return $this->hasMany(ClassroomFriendInvite::class, 'inviter_id');
    }

    public function receivedClassroomFriendInvites(): HasMany
    {
        return $this->hasMany(ClassroomFriendInvite::class, 'invitee_id');
    }
}
